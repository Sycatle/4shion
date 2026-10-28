<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_methods', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('carrier')->nullable();
            $table->string('delay')->nullable();
            $table->unsignedInteger('price');
            $table->unsignedInteger('free_threshold')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('shipping_method_id')->nullable()->constrained()->nullOnDelete();
            $table->string('shipping_method_name')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->unsignedInteger('refunded_total')->default(0);
        });

        Schema::create('shipments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('carrier')->nullable();
            $table->string('tracking_number')->nullable();
            $table->timestamp('shipped_at');
            $table->timestamps();
        });

        Schema::create('shipment_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('quantity');
        });

        Schema::create('order_returns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('number')->unique();
            $table->string('status')->index();
            $table->string('reason');
            $table->text('comment')->nullable();
            $table->text('decision_note')->nullable();
            $table->unsignedInteger('refund_amount')->default(0);
            $table->timestamp('received_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_return_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('quantity');
        });

        DB::statement('ALTER TABLE shipment_items ADD CONSTRAINT shipment_items_quantity_check CHECK (quantity > 0)');
        DB::statement('ALTER TABLE order_return_items ADD CONSTRAINT order_return_items_quantity_check CHECK (quantity > 0)');
        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_refunded_total_check CHECK (refunded_total >= 0)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_refunded_total_check');
        Schema::dropIfExists('order_return_items');
        Schema::dropIfExists('order_returns');
        Schema::dropIfExists('shipment_items');
        Schema::dropIfExists('shipments');
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('shipping_method_id');
            $table->dropColumn(['shipping_method_name', 'delivered_at', 'refunded_total']);
        });
        Schema::dropIfExists('shipping_methods');
    }
};
