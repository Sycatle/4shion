<?php

use App\Http\Controllers\Shop\HomeController;
use App\Livewire\Shop\ProductListing;
use App\Livewire\Shop\ProductShow;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('boutique', ProductListing::class)->name('shop.index');
Route::get('produits/{product:slug}', ProductShow::class)->name('shop.show');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
