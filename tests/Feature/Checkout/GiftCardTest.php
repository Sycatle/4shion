<?php

declare(strict_types=1);

use App\Actions\Checkout\CreateOrder;
use App\Actions\Checkout\StartCheckout;
use App\Actions\Orders\CancelOrder;
use App\Actions\Orders\MarkOrderPaid;
use App\Actions\Orders\RefundOrder;
use App\Cart\CartManager;
use App\Enums\OrderStatus;
use App\Exceptions\Cart\CartException;
use App\Mail\GiftCardsIssued;
use App\Models\GiftCard;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

function giftCardVariant(int $value = 5000): ProductVariant
{
    return ProductVariant::factory()
        ->for(Product::factory()->giftCard()->create(['base_price' => $value]))
        ->create(['stock' => 0, 'size' => 'Carte', 'color' => 'Cadeau']);
}

it('sells a gift card without stock and without shipping', function (): void {
    $user = User::factory()->create();
    app(CartManager::class)->add(giftCardVariant(5000), 2);

    $order = app(CreateOrder::class)($user, address());

    expect($order->subtotal)->toBe(10000)
        ->and($order->shipping_total)->toBe(0)
        ->and($order->vat_total)->toBe(0)
        ->and($order->items->sole()->is_gift_card)->toBeTrue()
        ->and($order->items->sole()->backordered_quantity)->toBe(0);
});

it('issues and emails one card per unit once the order is paid', function (): void {
    Mail::fake();
    $user = User::factory()->create();
    app(CartManager::class)->add(giftCardVariant(5000), 2);
    $order = app(CreateOrder::class)($user, address());

    app(MarkOrderPaid::class)($order);
    app(MarkOrderPaid::class)($order);

    $cards = GiftCard::query()->get();

    expect($cards)->toHaveCount(2)
        ->and($cards->pluck('balance')->all())->toBe([5000, 5000])
        ->and($cards->first()->user_id)->toBe($user->id)
        ->and($cards->first()->code)->toMatch('/^GC-[A-Z2-9]{4}-[A-Z2-9]{4}-[A-Z2-9]{4}$/');
    Mail::assertQueued(GiftCardsIssued::class, 1);
});

it('uses a gift card partially and charges the rest to the card', function (): void {
    fakeGateway();
    $user = User::factory()->create();
    $giftCard = GiftCard::factory()->worth(2000)->create();
    cartWith(price: 5000);
    app(CartManager::class)->applyGiftCard(strtolower($giftCard->code));

    $order = app(CreateOrder::class)($user, address());

    expect($order->total)->toBe(5490)
        ->and($order->gift_card_total)->toBe(2000)
        ->and($order->amountDue())->toBe(3490)
        ->and($giftCard->fresh()->balance)->toBe(0)
        ->and($order->giftCardRedemptions)->toHaveCount(1);
});

it('pays an order covered by a gift card without going through the payment provider', function (): void {
    $gateway = fakeGateway();
    $user = User::factory()->create();
    $giftCard = GiftCard::factory()->worth(20000)->create();
    cartWith(price: 5000);
    app(CartManager::class)->applyGiftCard($giftCard->code);

    $url = app(StartCheckout::class)($user, address());
    $order = $user->orders()->sole();

    expect($order->status)->toBe(OrderStatus::Paid)
        ->and($order->stripe_session_id)->toBeNull()
        ->and($url)->toBe(route('account.orders.show', $order))
        ->and($giftCard->fresh()->balance)->toBe(20000 - 5490);
    expect($gateway->expired)->toBe([]);
});

it('gives the gift card balance back when the order is cancelled', function (): void {
    fakeGateway();
    $giftCard = GiftCard::factory()->worth(2000)->create();
    cartWith(price: 5000);
    app(CartManager::class)->applyGiftCard($giftCard->code);
    $order = app(CreateOrder::class)(User::factory()->create(), address());

    app(CancelOrder::class)($order);
    app(CancelOrder::class)($order);

    expect($giftCard->fresh()->balance)->toBe(2000);
});

it('refunds only the card part and restores the gift card on a refund', function (): void {
    $gateway = fakeGateway();
    $giftCard = GiftCard::factory()->worth(2000)->create();
    cartWith(price: 5000);
    app(CartManager::class)->applyGiftCard($giftCard->code);
    $order = app(CreateOrder::class)(User::factory()->create(), address());
    app(MarkOrderPaid::class)($order, 'pi_test_123');

    app(RefundOrder::class)($order->fresh());

    expect($gateway->refunded)->toBe(['pi_test_123'])
        ->and($giftCard->fresh()->balance)->toBe(2000)
        ->and($order->fresh()->status)->toBe(OrderStatus::Refunded);
});

it('refunds an order fully paid by gift card without calling the provider', function (): void {
    $gateway = fakeGateway();
    $giftCard = GiftCard::factory()->worth(20000)->create();
    cartWith(price: 5000);
    app(CartManager::class)->applyGiftCard($giftCard->code);
    $order = app(CreateOrder::class)(User::factory()->create(), address());
    app(MarkOrderPaid::class)($order);

    app(RefundOrder::class)($order->fresh());

    expect($gateway->refunded)->toBe([])
        ->and($giftCard->fresh()->balance)->toBe(20000);
});

it('voids the cards bought with an order that is refunded', function (): void {
    fakeGateway();
    app(CartManager::class)->add(giftCardVariant(5000));
    $order = app(CreateOrder::class)(User::factory()->create(), address());
    app(MarkOrderPaid::class)($order, 'pi_test_123');

    app(RefundOrder::class)($order->fresh());

    expect(GiftCard::query()->sole()->is_active)->toBeFalse();
});

it('rejects unknown, expired and empty gift cards', function (GiftCard|string $card): void {
    cartWith();

    app(CartManager::class)->applyGiftCard($card instanceof GiftCard ? $card->code : $card);
})->with([
    'unknown' => 'GC-NOPE-NOPE-NOPE',
    'expired' => fn () => GiftCard::factory()->expired()->create(),
    'empty' => fn () => GiftCard::factory()->worth(0)->create(),
])->throws(CartException::class, 'carte cadeau');
