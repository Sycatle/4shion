<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Stock ledger, per-variant inventory settings, pre-orders and back-in-stock alerts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason');
            // Signed: positive when stock comes in, negative when it goes out.
            $table->integer('quantity');
            $table->unsignedInteger('stock_after');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['product_variant_id', 'created_at']);
        });

        Schema::table('product_variants', function (Blueprint $table): void {
            $table->unsignedSmallInteger('low_stock_threshold')->nullable()->after('stock');
            $table->boolean('preorder_enabled')->default(false)->after('low_stock_threshold');
            $table->date('preorder_available_on')->nullable()->after('preorder_enabled');
            $table->unsignedInteger('preorder_limit')->nullable()->after('preorder_available_on');
        });

        Schema::table('order_items', function (Blueprint $table): void {
            // Units ordered without stock (pre-order), waiting for a restock to be allocated.
            $table->unsignedInteger('backordered_quantity')->default(0)->after('quantity');
        });

        Schema::create('stock_alerts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->unique(['product_variant_id', 'email']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE stock_movements ADD CONSTRAINT stock_movements_quantity_check CHECK (quantity <> 0 AND stock_after >= 0)');
            DB::statement('ALTER TABLE order_items ADD CONSTRAINT order_items_backordered_check CHECK (backordered_quantity >= 0 AND backordered_quantity <= quantity)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE order_items DROP CONSTRAINT IF EXISTS order_items_backordered_check');
        }

        Schema::dropIfExists('stock_alerts');

        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropColumn('backordered_quantity');
        });

        Schema::table('product_variants', function (Blueprint $table): void {
            $table->dropColumn(['low_stock_threshold', 'preorder_enabled', 'preorder_available_on', 'preorder_limit']);
        });

        Schema::dropIfExists('stock_movements');
    }
};
