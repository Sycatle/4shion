<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;

/** Reads the visitor's cookie choices (set by the cookie banner). Without a choice, only essentials are allowed. */
final class Consent
{
    public const string COOKIE = 'cookie_consent';

    public static function allows(Request $request, string $category): bool
    {
        $raw = $request->cookie(self::COOKIE);
        $choices = is_string($raw) ? json_decode($raw, true) : null;

        return is_array($choices) && ($choices[$category] ?? false) === true;
    }
}
