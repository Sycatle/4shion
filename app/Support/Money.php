<?php

declare(strict_types=1);

namespace App\Support;

final class Money
{
    /**
     * Format an amount expressed in cents, e.g. 4990 => "49,90 €".
     */
    public static function format(int $cents): string
    {
        return number_format($cents / 100, 2, ',', "\u{202F}")."\u{00A0}€";
    }
}
