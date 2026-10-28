<?php

declare(strict_types=1);

use App\Http\Controllers\Account\CreditNoteController;
use App\Http\Controllers\Account\InvoiceController;
use App\Http\Controllers\Account\PersonalDataController;
use App\Http\Controllers\Seo\MerchantFeedController;
use App\Http\Controllers\Seo\RobotsController;
use App\Http\Controllers\Seo\SitemapController;
use App\Http\Controllers\Shop\CheckoutCancelController;
use App\Http\Controllers\Shop\HomeController;
use App\Http\Controllers\Shop\JournalController;
use App\Http\Controllers\Shop\LocaleController;
use App\Http\Controllers\Shop\NewsletterController;
use App\Http\Controllers\Shop\SharedWishlistController;
use App\Livewire\Account\AddressBook;
use App\Livewire\Account\OrderList;
use App\Livewire\Account\OrderShow;
use App\Livewire\Account\ReturnForm;
use App\Livewire\Account\Wishlist;
use App\Livewire\Actions\Logout;
use App\Livewire\Shop\CartPage;
use App\Livewire\Shop\CheckoutPage;
use App\Livewire\Shop\CheckoutSuccess;
use App\Livewire\Shop\ProductListing;
use App\Livewire\Shop\ProductShow;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::middleware('throttle:60,1')->group(function (): void {
    Route::get('robots.txt', RobotsController::class)->name('robots');
    Route::get('sitemap.xml', SitemapController::class)->name('sitemap');
    Route::get('feeds/google-merchant.xml', MerchantFeedController::class)->name('feeds.merchant');
});

Route::get('langue/{locale}', LocaleController::class)->name('locale');
Route::get('boutique', ProductListing::class)->name('shop.index');
Route::get('categories/{categorySlug}', ProductListing::class)->name('shop.category');
Route::get('journal', [JournalController::class, 'index'])->name('journal.index');
Route::get('journal/{post:slug}', [JournalController::class, 'show'])->name('journal.show');
Route::middleware('throttle:20,1')->group(function (): void {
    Route::get('newsletter/confirmer/{token}', [NewsletterController::class, 'confirm'])->name('newsletter.confirm');
    Route::get('newsletter/desinscription/{token}', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');
    Route::get('favoris/{token}', SharedWishlistController::class)->whereUuid('token')->name('wishlist.shared');
});
Route::get('produits/{product:slug}', ProductShow::class)->name('shop.show');
Route::get('panier', CartPage::class)->name('cart');

Route::view('conditions-de-vente', 'legal.terms')->name('legal.terms');
Route::view('mentions-legales', 'legal.notice')->name('legal.notice');
Route::view('confidentialite', 'legal.privacy')->name('legal.privacy');
Route::view('contact', 'legal.contact')->name('contact');

Route::get('commande', CheckoutPage::class)->name('checkout');

Route::middleware('auth')->prefix('commande')->group(function (): void {
    Route::get('succes', CheckoutSuccess::class)->name('checkout.success');
    Route::get('annulee/{order:number}', CheckoutCancelController::class)->name('checkout.cancel');
});

Route::middleware('auth')->prefix('compte')->group(function (): void {
    Route::get('commandes', OrderList::class)->name('account.orders');
    Route::get('commandes/{order:number}', OrderShow::class)->name('account.orders.show');
    Route::get('commandes/{order:number}/facture', InvoiceController::class)->name('account.orders.invoice');
    Route::get('favoris', Wishlist::class)->name('account.wishlist');
    Route::get('donnees', PersonalDataController::class)->name('account.data.export');
    Route::get('commandes/{order:number}/retour', ReturnForm::class)->name('account.orders.return');
    Route::get('commandes/{order:number}/retours/{return:number}/avoir', CreditNoteController::class)->name('account.returns.credit-note');
    Route::get('adresses', AddressBook::class)->name('account.addresses');
    Route::view('profil', 'profile')->name('profile');
});

Route::redirect('dashboard', '/compte/commandes')->name('dashboard');

Route::post('deconnexion', function (Logout $logout) {
    $logout();

    return to_route('home');
})->middleware('auth')->name('logout');

require __DIR__.'/auth.php';
