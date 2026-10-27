<?php

declare(strict_types=1);

use App\Http\Controllers\Account\InvoiceController;
use App\Http\Controllers\Shop\CheckoutCancelController;
use App\Http\Controllers\Shop\HomeController;
use App\Livewire\Account\AddressBook;
use App\Livewire\Account\OrderList;
use App\Livewire\Account\OrderShow;
use App\Livewire\Actions\Logout;
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

Route::view('conditions-de-vente', 'legal.terms')->name('legal.terms');
Route::view('mentions-legales', 'legal.notice')->name('legal.notice');
Route::view('confidentialite', 'legal.privacy')->name('legal.privacy');
Route::view('contact', 'legal.contact')->name('contact');

Route::middleware('auth')->prefix('commande')->group(function (): void {
    Route::get('/', CheckoutPage::class)->name('checkout');
    Route::get('succes', CheckoutSuccess::class)->name('checkout.success');
    Route::get('annulee/{order:number}', CheckoutCancelController::class)->name('checkout.cancel');
});

Route::middleware('auth')->prefix('compte')->group(function (): void {
    Route::get('commandes', OrderList::class)->name('account.orders');
    Route::get('commandes/{order:number}', OrderShow::class)->name('account.orders.show');
    Route::get('commandes/{order:number}/facture', InvoiceController::class)->name('account.orders.invoice');
    Route::get('adresses', AddressBook::class)->name('account.addresses');
    Route::view('profil', 'profile')->name('profile');
});

Route::redirect('dashboard', '/compte/commandes')->name('dashboard');

Route::post('deconnexion', function (Logout $logout) {
    $logout();

    return to_route('home');
})->middleware('auth')->name('logout');

require __DIR__.'/auth.php';
