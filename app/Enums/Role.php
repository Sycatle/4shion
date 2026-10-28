<?php

declare(strict_types=1);

namespace App\Enums;

/** Back-office roles. The admin sees everything; the others only the areas they work in. */
enum Role: string
{
    case Admin = 'admin';
    case Preparer = 'preparer';
    case Support = 'support';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrateur',
            self::Preparer => 'Préparateur',
            self::Support => 'Support',
        };
    }

    /**
     * Back-office areas the role may open.
     *
     * @return list<string>|null null means every area
     */
    public function areas(): ?array
    {
        return match ($this) {
            self::Admin => null,
            self::Preparer => ['orders', 'stock', 'products'],
            self::Support => ['orders', 'customers', 'reviews'],
        };
    }

    public function canAccess(string $area): bool
    {
        $areas = $this->areas();

        return $areas === null || in_array($area, $areas, true);
    }
}
