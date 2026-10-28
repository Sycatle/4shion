<?php

declare(strict_types=1);

namespace App\Enums;

enum PromotionType: string
{
    case SpendThreshold = 'spend_threshold';
    case BuyXGetCheapestFree = 'buy_x_get_cheapest_free';

    public function label(): string
    {
        return match ($this) {
            self::SpendThreshold => 'Remise par palier',
            self::BuyXGetCheapestFree => 'Le moins cher offert',
        };
    }
}
