<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Scheduled sales, automatic cart promotions and coupon restrictions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->unsignedTinyInteger('sale_percent')->nullable()->after('base_price');
            $table->timestamp('sale_starts_at')->nullable()->after('sale_percent');
            $table->timestamp('sale_ends_at')->nullable()->after('sale_starts_at');
        });

        Schema::create('promotions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            // Spend threshold: minimum eligible subtotal in cents and percentage off.
            $table->unsignedInteger('min_subtotal')->nullable();
            $table->unsignedTinyInteger('percent')->nullable();
            // Buy X: every group of X eligible units makes its cheapest unit free.
            $table->unsignedTinyInteger('buy_quantity')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('coupons', function (Blueprint $table): void {
            $table->unsignedInteger('max_uses_per_user')->nullable()->after('max_uses');
            $table->boolean('first_order_only')->default(false)->after('max_uses_per_user');
            $table->boolean('excludes_sale_items')->default(false)->after('first_order_only');
        });

        Schema::create('category_coupon', function (Blueprint $table): void {
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();

            $table->primary(['coupon_id', 'category_id']);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('promotion_id')->nullable()->after('coupon_code')->constrained()->nullOnDelete();
            $table->string('promotion_name')->nullable()->after('promotion_id');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE products ADD CONSTRAINT products_sale_percent_check CHECK (sale_percent IS NULL OR sale_percent BETWEEN 1 AND 90)');
            DB::statement('ALTER TABLE promotions ADD CONSTRAINT promotions_values_check CHECK ((percent IS NULL OR percent BETWEEN 1 AND 100) AND (buy_quantity IS NULL OR buy_quantity >= 2))');
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('promotion_id');
            $table->dropColumn('promotion_name');
        });

        Schema::dropIfExists('category_coupon');

        Schema::table('coupons', function (Blueprint $table): void {
            $table->dropColumn(['max_uses_per_user', 'first_order_only', 'excludes_sale_items']);
        });

        Schema::dropIfExists('promotions');

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE products DROP CONSTRAINT IF EXISTS products_sale_percent_check');
        }

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['sale_percent', 'sale_starts_at', 'sale_ends_at']);
        });
    }
};
