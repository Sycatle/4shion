<?php

declare(strict_types=1);

namespace App\Providers;

use App\Cart\CartManager;
use App\Http\Middleware\SetLocale;
use App\Payments\CheckoutGateway;
use App\Payments\StripeCheckoutGateway;
use App\Support\Consent;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(CartManager::class);
        $this->app->bind(CheckoutGateway::class, StripeCheckoutGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // @consent('analytics') … @endconsent: render third-party scripts only with the visitor's agreement.
        // Livewire's own update requests must see the visitor's language too.
        Livewire::addPersistentMiddleware([SetLocale::class]);

        Blade::if('consent', fn (string $category): bool => Consent::allows(request(), $category));
    }
}
