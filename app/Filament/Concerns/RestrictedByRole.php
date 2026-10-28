<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Models\User;
use Override;

/** Limits a resource to the staff roles allowed on its area (see Role::areas()). */
trait RestrictedByRole
{
    /** Back-office area the resource belongs to. */
    abstract protected static function area(): string;

    #[Override]
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->canAccessArea(static::area());
    }
}
