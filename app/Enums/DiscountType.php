<?php

declare(strict_types=1);

namespace App\Enums;

enum DiscountType: string
{
    case Percent = 'percent';
    case Fixed = 'fixed';
    case FreeShipping = 'free_shipping';

    public function label(): string
    {
        return match ($this) {
            self::Percent => 'Pourcentage',
            self::Fixed => 'Montant fixe',
            self::FreeShipping => 'Livraison offerte',
        };
    }
}
