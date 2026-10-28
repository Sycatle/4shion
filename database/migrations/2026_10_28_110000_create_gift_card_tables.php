<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gift cards: sold as a product, issued once the order is paid, redeemed at checkout.
 * `orders.total` stays the order value; `gift_card_total` is the part settled by a card,
 * so the amount charged to the customer is `total - gift_card_total`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->boolean('is_gift_card')->default(false)->after('is_featured');
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->boolean('is_gift_card')->default(false)->after('backordered_quantity');
        });

        Schema::create('gift_cards', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->unsignedInteger('initial_value');
            $table->unsignedInteger('balance');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('gift_card_redemptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('gift_card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->unsignedInteger('gift_card_total')->default(0)->after('total');
        });

        Schema::table('carts', function (Blueprint $table): void {
            $table->foreignId('gift_card_id')->nullable()->after('coupon_id')->constrained()->nullOnDelete();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE gift_cards ADD CONSTRAINT gift_cards_balance_check CHECK (balance >= 0 AND balance <= initial_value)');
            DB::statement('ALTER TABLE gift_card_redemptions ADD CONSTRAINT gift_card_redemptions_amount_check CHECK (amount > 0)');
            DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_gift_card_check CHECK (gift_card_total >= 0 AND gift_card_total <= total)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_gift_card_check');
        }

        Schema::table('carts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('gift_card_id');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('gift_card_total');
        });

        Schema::dropIfExists('gift_card_redemptions');
        Schema::dropIfExists('gift_cards');

        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropColumn('is_gift_card');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('is_gift_card');
        });
    }
};
