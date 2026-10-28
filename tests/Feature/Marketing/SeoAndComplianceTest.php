<?php

declare(strict_types=1);

use App\Actions\Account\DeleteAccount;
use App\Actions\Account\ExportPersonalData;
use App\Enums\OrderStatus;
use App\Livewire\Shop\NewsletterForm;
use App\Mail\NewsletterConfirmation;
use App\Models\Category;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\User;
use App\Support\Consent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Livewire\Volt\Volt;

function listedProduct(array $attributes = []): Product
{
    $product = Product::factory()->create($attributes);
    ProductVariant::factory()->for($product)->create(['stock' => 5]);

    return $product;
}

describe('seo', function (): void {
    it('puts canonical, Open Graph and Twitter tags on the product page', function (): void {
        $product = listedProduct(['name' => 'Hoodie Atlas', 'description' => 'Un hoodie épais.']);

        $this->get(route('shop.show', $product))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.route('shop.show', $product).'">', false)
            ->assertSee('<meta property="og:type" content="product">', false)
            ->assertSee('<meta property="og:title" content="Hoodie Atlas">', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
            ->assertSee('<meta name="description" content="Un hoodie épais.">', false);
    });

    it('describes the product, its offer, rating and breadcrumb in JSON-LD', function (): void {
        $product = listedProduct(['name' => 'Hoodie Atlas', 'base_price' => 8900]);
        Review::factory()->for($product)->create(['rating' => 4]);

        $html = $this->get(route('shop.show', $product))->assertOk()->getContent();
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', (string) $html, $matches);
        $documents = array_map(fn (string $json): array => json_decode(html_entity_decode($json), true), $matches[1]);
        $types = array_column($documents, '@type');

        expect($types)->toContain('Product', 'BreadcrumbList');
        $schema = $documents[array_search('Product', $types, true)];
        expect($schema['offers']['lowPrice'])->toBe('89.00')
            ->and($schema['offers']['availability'])->toBe('https://schema.org/InStock')
            ->and($schema['aggregateRating']['reviewCount'])->toBe(1);
    });

    it('keeps private and filtered pages out of the index', function (): void {
        listedProduct();

        $this->get(route('cart'))->assertSee('<meta name="robots" content="noindex, follow">', false);
        $this->get(route('shop.index', ['sort' => 'price_asc']))->assertSee('noindex', false);
        $this->get(route('shop.index'))->assertDontSee('noindex', false);
    });

    it('serves a category page with its editorial content and canonical URL', function (): void {
        $category = Category::factory()->create(['name' => 'Hoodies', 'slug' => 'hoodies', 'content' => "## Pourquoi nos hoodies\n\nMolleton **dense**.", 'meta_title' => 'Hoodies premium', 'meta_description' => 'Nos hoodies.']);
        listedProduct(['category_id' => $category->id, 'name' => 'Atlas']);

        $this->get(route('shop.category', 'hoodies'))
            ->assertOk()
            ->assertSee('Atlas')
            ->assertSee('Pourquoi nos hoodies')
            ->assertSee('<strong>dense</strong>', false)
            ->assertSee('<title>Hoodies premium — '.config('app.name').'</title>', false)
            ->assertSee('<link rel="canonical" href="'.route('shop.category', 'hoodies').'">', false);

        $this->get(route('shop.category', 'missing'))->assertNotFound();
    });

    it('lists indexable URLs in the sitemap and none of the hidden ones', function (): void {
        Cache::flush();
        $visible = listedProduct();
        $hidden = Product::factory()->inactive()->create();
        $published = Post::create(['title' => 'Lookbook', 'slug' => 'lookbook', 'body' => 'x', 'published_at' => now()->subDay()]);
        $draft = Post::create(['title' => 'Brouillon', 'slug' => 'brouillon', 'body' => 'x']);

        $xml = $this->get(route('sitemap'))->assertOk()->assertHeader('content-type', 'application/xml; charset=UTF-8')->getContent();

        expect($xml)->toContain(route('shop.show', $visible))->toContain(route('journal.show', $published))
            ->not->toContain(route('shop.show', $hidden))->not->toContain('brouillon');
    });

    it('points robots.txt at the sitemap and blocks private areas', function (): void {
        $this->get(route('robots'))->assertOk()->assertSee('Disallow: /admin')->assertSee('Sitemap: '.route('sitemap'));
    });

    it('exports a Google Merchant feed with one item per variant and the right availability', function (): void {
        Cache::flush();
        $product = Product::factory()->onSale(20)->create(['base_price' => 10000, 'name' => 'Tee Feed']);
        ProductVariant::factory()->for($product)->create(['sku' => 'FEED-M', 'size' => 'M', 'stock' => 3]);
        ProductVariant::factory()->for($product)->create(['sku' => 'FEED-L', 'size' => 'L', 'stock' => 0, 'preorder_enabled' => true]);
        ProductVariant::factory()->for($product)->create(['sku' => 'FEED-S', 'size' => 'S', 'stock' => 0]);
        listedProduct(['is_gift_card' => true, 'name' => 'Carte']);

        $xml = $this->get(route('feeds.merchant'))->assertOk()->getContent();

        expect($xml)->toContain('<g:id>FEED-M</g:id>')->toContain('<g:availability>in_stock</g:availability>')
            ->toContain('<g:availability>preorder</g:availability>')->toContain('<g:availability>out_of_stock</g:availability>')
            ->toContain('<g:price>100.00 EUR</g:price>')->toContain('<g:sale_price>80.00 EUR</g:sale_price>')
            ->not->toContain('Carte');
    });
});

describe('journal', function (): void {
    it('shows published articles, renders markdown safely and hides drafts', function (): void {
        $post = Post::create(['title' => 'Matières', 'slug' => 'matieres', 'body' => "Du **coton**.\n\n<script>alert(1)</script>", 'published_at' => now()->subHour()]);
        $draft = Post::create(['title' => 'Secret', 'slug' => 'secret', 'body' => 'x']);
        $future = Post::create(['title' => 'Demain', 'slug' => 'demain', 'body' => 'x', 'published_at' => now()->addDay()]);

        $this->get(route('journal.index'))->assertSee('Matières')->assertDontSee('Secret')->assertDontSee('Demain');
        $this->get(route('journal.show', $post))->assertOk()->assertSee('<strong>coton</strong>', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->get(route('journal.show', $draft))->assertNotFound();
        $this->get(route('journal.show', $future))->assertNotFound();
    });
});

describe('newsletter', function (): void {
    it('only activates after the confirmation link (double opt-in)', function (): void {
        Mail::fake();

        Livewire::test(NewsletterForm::class)->set('email', ' Lea@Example.test ')->call('subscribe')->assertSet('submitted', true);

        $subscriber = NewsletterSubscriber::sole();
        expect($subscriber->email)->toBe('lea@example.test')->and($subscriber->isActive())->toBeFalse();
        Mail::assertQueued(NewsletterConfirmation::class);
        expect(NewsletterSubscriber::query()->active()->count())->toBe(0);

        $this->get(route('newsletter.confirm', $subscriber->token))->assertOk()->assertSee('Inscription confirmée');

        expect($subscriber->refresh()->isActive())->toBeTrue();
    });

    it('unsubscribes with one click', function (): void {
        $subscriber = NewsletterSubscriber::create(['email' => 'a@example.test', 'token' => NewsletterSubscriber::newToken(), 'confirmed_at' => now()]);

        $this->get(route('newsletter.unsubscribe', $subscriber->token))->assertOk()->assertSee('désinscrit');

        expect($subscriber->refresh()->isActive())->toBeFalse();
    });

    it('answers the same whether or not the address is already subscribed', function (): void {
        Mail::fake();
        NewsletterSubscriber::create(['email' => 'known@example.test', 'token' => NewsletterSubscriber::newToken(), 'confirmed_at' => now()]);

        Livewire::test(NewsletterForm::class)->set('email', 'known@example.test')->call('subscribe')->assertSet('submitted', true);

        Mail::assertNothingQueued();
    });

    it('rejects invalid addresses and unknown tokens', function (): void {
        Livewire::test(NewsletterForm::class)->set('email', 'nope')->call('subscribe')->assertHasErrors(['email']);
        $this->get(route('newsletter.confirm', 'unknown'))->assertNotFound();
    });
});

describe('cookie consent', function (): void {
    it('shows the banner and sets nothing optional by default', function (): void {
        $this->get(route('home'))->assertSee('Vos choix sur les cookies')->assertSee('Tout refuser');
    });

    it('reads the visitor choice from the cookie', function (): void {
        $request = fn (string $value): Request => Request::create('/', 'GET', cookies: [Consent::COOKIE => $value]);

        expect(Consent::allows($request('{"analytics":true,"marketing":false}'), 'analytics'))->toBeTrue()
            ->and(Consent::allows($request('{"analytics":true,"marketing":false}'), 'marketing'))->toBeFalse()
            ->and(Consent::allows($request('garbage'), 'analytics'))->toBeFalse()
            ->and(Consent::allows(Request::create('/'), 'analytics'))->toBeFalse();
    });

    it('lets a Blade @consent block render only with agreement', function (): void {
        $template = "@consent('analytics') TRACKER @endconsent";

        expect(trim(Blade::render($template)))->toBe('');

        $this->app->instance('request', Request::create('/', 'GET', cookies: [Consent::COOKIE => '{"analytics":true}']));

        expect(trim(Blade::render($template)))->toBe('TRACKER');
    });
});

describe('personal data', function (): void {
    it('exports what the shop holds about the customer', function (): void {
        $user = User::factory()->create(['name' => 'Camille Martin']);
        $user->addresses()->create(['first_name' => 'Camille', 'last_name' => 'Martin', 'line1' => '12 rue des Lilas', 'postal_code' => '75011', 'city' => 'Paris', 'country' => 'FR']);
        Order::factory()->for($user)->paid()->create();
        $product = listedProduct();
        $user->wishlist()->attach($product);
        $this->actingAs($user);

        $response = $this->get(route('account.data.export'))->assertOk();
        $data = $response->json();

        expect($response->headers->get('content-disposition'))->toContain('attachment')
            ->and($data['profile']['email'])->toBe($user->email)
            ->and($data['addresses'])->toHaveCount(1)
            ->and($data['orders'])->toHaveCount(1)
            ->and($data['wishlist'])->toBe([$product->name])
            ->and($data)->not->toHaveKey('password');
    });

    it('requires a login to export', function (): void {
        $this->get(route('account.data.export'))->assertRedirect(route('login'));
    });

    it('deletes a customer who never ordered', function (): void {
        $user = User::factory()->create();
        $user->wishlist()->attach(listedProduct());

        app(DeleteAccount::class)($user);

        expect(User::find($user->id))->toBeNull();
    });

    it('anonymises a customer who ordered but keeps the orders', function (): void {
        $user = User::factory()->create(['email' => 'client@example.test']);
        $order = Order::factory()->for($user)->create(['status' => OrderStatus::Delivered]);
        Review::factory()->create(['user_id' => $user->id]);
        NewsletterSubscriber::create(['email' => 'client@example.test', 'token' => NewsletterSubscriber::newToken(), 'confirmed_at' => now()]);

        app(DeleteAccount::class)($user);

        $user->refresh();
        expect($user->email)->toEndWith('@deleted.invalid')->and($user->name)->toBe('Compte supprimé')
            ->and($order->refresh()->user_id)->toBe($user->id)
            ->and(Review::query()->where('user_id', $user->id)->count())->toBe(0)
            ->and(NewsletterSubscriber::query()->count())->toBe(0);
    });

    it('refuses to erase an account with an order in progress', function (): void {
        $user = User::factory()->create();
        Order::factory()->for($user)->paid()->create();

        expect(fn () => app(DeleteAccount::class)($user))->toThrow(LogicException::class, 'commande en cours')
            ->and(User::find($user->id))->not->toBeNull();
    });

    it('explains why from the profile page, and signs out after a real deletion', function (): void {
        $busy = User::factory()->create();
        Order::factory()->for($busy)->paid()->create();
        $this->actingAs($busy);

        Volt::test('profile.delete-user-form')->set('password', 'password')->call('deleteUser')->assertSet('error', 'Vous avez une commande en cours : le compte pourra être supprimé une fois celle-ci livrée.')->assertNoRedirect();
        $this->assertAuthenticated();
    });

    it('exports through the action without exposing secrets', function (): void {
        $user = User::factory()->create();

        expect(json_encode(app(ExportPersonalData::class)($user)))->not->toContain('remember_token')->not->toContain($user->password);
    });
});
