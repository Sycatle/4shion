<?php

declare(strict_types=1);

namespace App\Enums;

enum StockMovementReason: string
{
    case Restock = 'restock';
    case Sale = 'sale';
    case Cancellation = 'cancellation';
    case Refund = 'refund';
    case Adjustment = 'adjustment';
    case PreorderAllocation = 'preorder_allocation';

    public function label(): string
    {
        return match ($this) {
            self::Restock => 'Réassort',
            self::Sale => 'Vente',
            self::Cancellation => 'Annulation',
            self::Refund => 'Remboursement',
            self::Adjustment => 'Ajustement',
            self::PreorderAllocation => 'Allocation précommande',
        };
    }
}
