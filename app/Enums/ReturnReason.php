<?php

declare(strict_types=1);

namespace App\Enums;

enum ReturnReason: string
{
    case Withdrawal = 'withdrawal';
    case WrongSize = 'wrong_size';
    case Defective = 'defective';
    case NotAsDescribed = 'not_as_described';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Withdrawal => 'Rétractation (je n’en veux plus)',
            self::WrongSize => 'Taille inadaptée',
            self::Defective => 'Article défectueux',
            self::NotAsDescribed => 'Ne correspond pas à la description',
            self::Other => 'Autre',
        };
    }
}
