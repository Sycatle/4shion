<?php

use App\Http\Controllers\Shop\CheckoutCancelController;
use App\Http\Controllers\Shop\HomeController;
use App\Livewire\Shop\CartPage;
use App\Livewire\Shop\CheckoutPage;
use App\Livewire\Shop\CheckoutSuccess;
use App\Livewire\Shop\ProductListing;
use App\Livewire\Shop\ProductShow;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('boutique', ProductListing::class)->name('shop.index');
Route::get('produits/{product:slug}', ProductShow::class)->name('shop.show');
Route::get('panier', CartPage::class)->name('cart');

Route::middleware('auth')->prefix('commande')->group(function () {
    Route::get('/', CheckoutPage::class)->name('checkout');
    Route::get('succes', CheckoutSuccess::class)->name('checkout.success');
    Route::get('annulee/{order:number}', CheckoutCancelController::class)->name('checkout.cancel');
});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
