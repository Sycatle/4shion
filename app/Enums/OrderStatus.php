<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente de paiement',
            self::Paid => 'Payée',
            self::Shipped => 'Expédiée',
            self::Delivered => 'Livrée',
            self::Cancelled => 'Annulée',
            self::Refunded => 'Remboursée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Paid, self::Delivered => 'success',
            self::Shipped => 'info',
            self::Cancelled, self::Refunded => 'danger',
        };
    }

    /** Statuses for which stock has been committed and payment received. */
    public function isPaid(): bool
    {
        return in_array($this, [self::Paid, self::Shipped, self::Delivered], true);
    }
}
