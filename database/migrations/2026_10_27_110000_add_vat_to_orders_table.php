<?php

use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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

        $rate = (float) config('shop.vat_rate');

        DB::table('orders')->select(['id', 'total'])->orderBy('id')->each(function (object $order) use ($rate): void {
            DB::table('orders')->where('id', $order->id)->update([
                'vat_rate' => $rate,
                'vat_total' => Order::vatIncludedIn((int) $order->total, $rate),
            ]);
        });

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
