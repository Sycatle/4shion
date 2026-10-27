<?php

declare(strict_types=1);

namespace App\Providers;

use App\Cart\CartManager;
use App\Payments\CheckoutGateway;
use App\Payments\StripeCheckoutGateway;
use Illuminate\Support\ServiceProvider;

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
        //
    }
}
