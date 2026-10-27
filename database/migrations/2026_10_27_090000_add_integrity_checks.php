<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * PostgreSQL ignores "unsigned": these checks make the database itself reject
 * negative stock or amounts, whatever code path writes them.
 */
return new class extends Migration
{
    /** @var array<string, array<string, string>> */
    private array $checks = [
        'product_variants' => ['product_variants_stock_check' => 'stock >= 0'],
        'cart_items' => ['cart_items_quantity_check' => 'quantity > 0'],
        'coupons' => ['coupons_used_count_check' => 'used_count >= 0'],
        'orders' => [
            'orders_amounts_check' => 'subtotal >= 0 AND discount_total >= 0 AND shipping_total >= 0',
            'orders_total_check' => 'total = subtotal - discount_total + shipping_total',
        ],
        'order_items' => [
            'order_items_quantity_check' => 'quantity > 0',
            'order_items_total_check' => 'total = unit_price * quantity',
        ],
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->checks as $table => $constraints) {
            foreach ($constraints as $name => $expression) {
                DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$name} CHECK ({$expression})");
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->checks as $table => $constraints) {
            foreach (array_keys($constraints) as $name) {
                DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$name}");
            }
        }
    }
};
