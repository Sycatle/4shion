<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wishlist_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'product_id']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->uuid('wishlist_token')->nullable()->unique();
        });

        Schema::table('reviews', function (Blueprint $table): void {
            $table->text('merchant_reply')->nullable();
            $table->timestamp('merchant_replied_at')->nullable();
            $table->unsignedInteger('helpful_count')->default(0);
        });

        Schema::create('review_votes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('review_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['review_id', 'user_id']);
        });

        Schema::table('carts', function (Blueprint $table): void {
            $table->timestamp('reminder_sent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('carts', fn (Blueprint $table) => $table->dropColumn('reminder_sent_at'));
        Schema::dropIfExists('review_votes');
        Schema::table('reviews', fn (Blueprint $table) => $table->dropColumn(['merchant_reply', 'merchant_replied_at', 'helpful_count']));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('wishlist_token'));
        Schema::dropIfExists('wishlist_items');
    }
};
