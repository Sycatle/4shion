<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Enums\OrderStatus;
use App\Models\Cart;
use App\Models\NewsletterSubscriber;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

final class DeleteAccount
{
    /**
     * Erase a customer (GDPR art. 17). Orders must be kept for accounting, so a customer who ordered
     * keeps an anonymised account row that can no longer sign in; the others are deleted outright.
     * Everything else personal goes in both cases.
     *
     * @throws LogicException while an order is still being paid, prepared or delivered
     */
    public function __invoke(User $user): void
    {
        if ($user->isStaff()) {
            throw new LogicException('Un compte de l’équipe ne peut pas être supprimé ici.');
        }

        if ($user->orders()->whereIn('status', [OrderStatus::Pending, OrderStatus::Paid, OrderStatus::Shipped])->exists()) {
            throw new LogicException('Vous avez une commande en cours : le compte pourra être supprimé une fois celle-ci livrée.');
        }

        DB::transaction(function () use ($user): void {
            NewsletterSubscriber::query()->where('email', $user->email)->delete();
            $user->addresses()->delete();
            $user->wishlist()->detach();
            Review::query()->where('user_id', $user->id)->delete();
            Cart::query()->where('user_id', $user->id)->delete();

            if (! $user->orders()->exists()) {
                $user->delete();

                return;
            }

            $user->forceFill([
                'name' => 'Compte supprimé',
                'email' => "deleted-{$user->id}-".Str::lower(Str::random(8)).'@deleted.invalid',
                'password' => Str::random(64),
                'remember_token' => null,
                'wishlist_token' => null,
                'stripe_id' => null,
                'pm_type' => null,
                'pm_last_four' => null,
                'email_verified_at' => null,
            ])->save();
        });
    }
}
