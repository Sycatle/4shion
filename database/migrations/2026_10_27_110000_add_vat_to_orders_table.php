<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshot the VAT on each order: an invoice must keep showing the rate that applied
 * at purchase time, whatever the configuration says later.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('vat_rate', 5, 2)->nullable()->after('total');
            $table->unsignedInteger('vat_total')->nullable()->after('vat_rate');
        });

        // Same rounding as Order::vatIncludedIn(), done in one set-based statement.
        $rate = Config::float('shop.vat_rate');
        DB::update('UPDATE orders SET vat_rate = ?, vat_total = total - ROUND(total / CAST(? AS numeric))', [$rate, 1 + $rate / 100]);

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('vat_rate', 5, 2)->nullable(false)->change();
            $table->unsignedInteger('vat_total')->nullable(false)->change();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_vat_check CHECK (vat_rate >= 0 AND vat_total >= 0 AND vat_total <= total)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_vat_check');
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['vat_rate', 'vat_total']);
        });
    }
};
