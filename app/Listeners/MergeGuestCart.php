<?php

namespace App\Listeners;

use App\Cart\CartManager;
use App\Models\User;
use Illuminate\Auth\Events\Login;

class MergeGuestCart
{
    public function __construct(private CartManager $cart) {}

    public function handle(Login $event): void
    {
        if ($event->user instanceof User) {
            $this->cart->mergeGuestCartInto($event->user);
        }
    }
}
