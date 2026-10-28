<?php

declare(strict_types=1);

use App\Catalog\RecentlyViewed;
use App\Catalog\Recommender;
use App\Console\Commands\RemindAbandonedCarts;
use App\Enums\OrderStatus;
use App\Filament\Resources\Reviews\Pages\ManageReviews;
use App\Livewire\Account\Wishlist;
use App\Livewire\Shop\CartPage;
use App\Livewire\Shop\ProductShow;
use App\Mail\AbandonedCartReminder;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

function stockedProduct(array $attributes = []): Product
{
    $product = Product::factory()->create($attributes);
    ProductVariant::factory()->for($product)->create(['stock' => 5]);

    return $product;
}

function paidOrderWith(Product ...$products): void
{
    $order = Order::factory()->for(User::factory())->create(['status' => OrderStatus::Paid]);

    foreach ($products as $product) {
        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $product->variants()->first()->id,
            'product_name' => $product->name,
            'variant_label' => 'M',
            'sku' => 'SKU-'.$product->id,
            'unit_price' => 1000,
            'quantity' => 1,
            'total' => 1000,
        ]);
    }
}

describe('wishlist', function (): void {
    it('toggles a product from its page', function (): void {
        $user = User::factory()->create();
        $product = stockedProduct();
        $this->actingAs($user);

        Livewire::test(ProductShow::class, ['product' => $product])
            ->call('toggleWishlist')
            ->assertSee('Retirer de mes favoris');

        expect($user->wishlist()->count())->toBe(1);

        Livewire::test(ProductShow::class, ['product' => $product])->call('toggleWishlist');

        expect($user->wishlist()->count())->toBe(0);
    });

    it('sends a guest to the login page', function (): void {
        Livewire::test(ProductShow::class, ['product' => stockedProduct()])
            ->call('toggleWishlist')
            ->assertRedirect(route('login'));
    });

    it('lists, removes and shares favourites', function (): void {
        $user = User::factory()->create(['name' => 'Camille Martin']);
        $kept = stockedProduct(['name' => 'Hoodie Gardé']);
        $removed = stockedProduct(['name' => 'Tee Retiré']);
        $user->wishlist()->attach([$kept->id, $removed->id]);
        $this->actingAs($user);

        Livewire::test(Wishlist::class)
            ->assertSee('Hoodie Gardé')
            ->call('remove', $removed->id)
            ->assertDontSee('Tee Retiré');

        $this->get(route('wishlist.shared', $user->wishlistToken()))
            ->assertOk()
            ->assertSee('Favoris de Camille')
            ->assertSee('Hoodie Gardé')
            ->assertDontSee('Tee Retiré');
    });

    it('404s on an unknown share token', function (): void {
        $this->get(route('wishlist.shared', 'nope'))->assertNotFound();
    });
});

describe('recommendations', function (): void {
    it('ranks products bought together, then falls back on the same category', function (): void {
        $shirts = Category::factory()->create();
        $main = stockedProduct(['category_id' => $shirts->id]);
        $often = stockedProduct();
        $rarely = stockedProduct();
        $sibling = stockedProduct(['category_id' => $shirts->id]);

        paidOrderWith($main, $often);
        paidOrderWith($main, $often);
        paidOrderWith($main, $rarely);

        $ids = app(Recommender::class)->boughtTogether($main, 4)->modelKeys();

        expect($ids)->toBe([$often->id, $rarely->id, $sibling->id]);
    });

    it('ignores unpaid orders', function (): void {
        $main = stockedProduct();
        $other = stockedProduct();
        $order = Order::factory()->for(User::factory())->create(['status' => OrderStatus::Cancelled]);

        foreach ([$main, $other] as $product) {
            OrderItem::create(['order_id' => $order->id, 'product_variant_id' => $product->variants()->first()->id, 'product_name' => 'x', 'variant_label' => 'M', 'sku' => 's'.$product->id, 'unit_price' => 1, 'quantity' => 1, 'total' => 1]);
        }

        expect(app(Recommender::class)->boughtTogether($main)->modelKeys())->not->toContain($other->id);
    });

    it('suggests other products of the cart categories', function (): void {
        $category = Category::factory()->create();
        $inCart = stockedProduct(['category_id' => $category->id]);
        $suggested = stockedProduct(['category_id' => $category->id]);
        stockedProduct();

        expect(app(Recommender::class)->forCart([$inCart->id])->modelKeys())->toBe([$suggested->id])
            ->and(app(Recommender::class)->forCart([]))->toBeEmpty();
    });

    it('shows the cross-sell row on the cart page', function (): void {
        $variant = cartWith();
        $suggested = stockedProduct(['category_id' => $variant->product->category_id, 'name' => 'Complément Idéal']);

        Livewire::test(CartPage::class)->assertSee('Pour compléter votre panier')->assertSee($suggested->name);
    });

    it('remembers recently viewed products, most recent first and without the current one', function (): void {
        [$a, $b, $c] = [stockedProduct(), stockedProduct(), stockedProduct()];
        $viewed = app(RecentlyViewed::class);

        foreach ([$a, $b, $c, $a] as $product) {
            $viewed->record($product);
        }

        expect($viewed->products()->modelKeys())->toBe([$a->id, $c->id, $b->id])
            ->and($viewed->products(except: $a)->modelKeys())->toBe([$c->id, $b->id]);
    });
});

describe('reviews', function (): void {
    it('shows the rating on listing cards', function (): void {
        $product = stockedProduct(['name' => 'Tee Noté']);
        Review::factory()->for($product)->create(['rating' => 4]);
        Review::factory()->for($product)->pending()->create(['rating' => 1]);

        $this->get(route('shop.index'))->assertSee('Tee Noté')->assertSee('(1)', false);
    });

    it('counts a helpful vote once per customer and lets them withdraw it', function (): void {
        $product = stockedProduct();
        $review = Review::factory()->for($product)->create();
        $voter = User::factory()->create();
        $this->actingAs($voter);

        $page = Livewire::test(ProductShow::class, ['product' => $product]);
        $page->call('voteHelpful', $review->id);
        expect($review->fresh()->helpful_count)->toBe(1);

        $page->call('voteHelpful', $review->id);
        expect($review->fresh()->helpful_count)->toBe(0);
    });

    it('does not let authors vote for their own review', function (): void {
        $product = stockedProduct();
        $review = Review::factory()->for($product)->create();
        $this->actingAs($review->user);

        Livewire::test(ProductShow::class, ['product' => $product])->call('voteHelpful', $review->id);

        expect($review->fresh()->helpful_count)->toBe(0);
    });

    it('displays the merchant reply published from the back-office', function (): void {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $product = stockedProduct();
        $review = Review::factory()->for($product)->create();

        Livewire::test(ManageReviews::class)
            ->removeTableFilter('approved')
            ->callAction(TestAction::make('reply')->table($review), data: ['merchant_reply' => 'Merci pour votre retour !'])
            ->assertHasNoActionErrors();

        expect($review->fresh()->merchant_replied_at)->not->toBeNull();

        Livewire::test(ProductShow::class, ['product' => $product])->assertSee('Merci pour votre retour !')->assertSee('Achat vérifié');
    });
});

describe('abandoned carts', function (): void {
    function abandonedCart(int $hoursIdle = 30, ?User $user = null): Cart
    {
        $cart = Cart::create(['user_id' => ($user ?? User::factory()->create())->id]);
        $item = CartItem::create(['cart_id' => $cart->id, 'product_variant_id' => ProductVariant::factory()->create(['stock' => 5])->id, 'quantity' => 1]);
        $item->forceFill(['updated_at' => now()->subHours($hoursIdle)])->saveQuietly();

        return $cart;
    }

    it('reminds a customer once after a day of inactivity', function (): void {
        Mail::fake();
        config(['shop.abandoned_cart.coupon' => 'REVIENS10']);
        $cart = abandonedCart();

        $this->artisan(RemindAbandonedCarts::class)->assertSuccessful();
        $this->artisan(RemindAbandonedCarts::class)->assertSuccessful();

        Mail::assertQueued(AbandonedCartReminder::class, 1);
        Mail::assertQueued(AbandonedCartReminder::class, fn (AbandonedCartReminder $mail): bool => $mail->couponCode === 'REVIENS10' && $mail->hasTo($cart->user->email));
        expect($cart->fresh()->reminder_sent_at)->not->toBeNull();
    });

    it('leaves fresh, stale, empty and already converted carts alone', function (): void {
        Mail::fake();
        abandonedCart(hoursIdle: 2);
        abandonedCart(hoursIdle: 24 * 10);
        Cart::create(['user_id' => User::factory()->create()->id]);
        $buyer = User::factory()->create();
        abandonedCart(user: $buyer);
        Order::factory()->for($buyer)->create();

        $this->artisan(RemindAbandonedCarts::class)->assertSuccessful();

        Mail::assertNothingQueued();
    });

    it('renders the e-mail with the cart items and a link back', function (): void {
        $cart = abandonedCart()->load('items.variant.product');

        $html = new AbandonedCartReminder($cart)->render();

        expect($html)->toContain($cart->items->first()->variant->product->name)->toContain(route('cart'));
    });
});
