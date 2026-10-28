<?php

declare(strict_types=1);

namespace App\Enums;

enum ReturnStatus: string
{
    case Requested = 'requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Received = 'received';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Demandé',
            self::Approved => 'Accepté, en attente du colis',
            self::Rejected => 'Refusé',
            self::Received => 'Colis reçu',
            self::Refunded => 'Remboursé',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Requested => 'warning',
            self::Approved, self::Received => 'info',
            self::Rejected => 'danger',
            self::Refunded => 'success',
        };
    }

    /** @return list<self> */
    public function transitions(): array
    {
        return match ($this) {
            self::Requested => [self::Approved, self::Rejected],
            self::Approved => [self::Received, self::Rejected],
            self::Received => [self::Refunded],
            self::Rejected, self::Refunded => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->transitions(), true);
    }

    /** Whether the returned units still count against what the customer can send back. */
    public function isActive(): bool
    {
        return $this !== self::Rejected;
    }
}
