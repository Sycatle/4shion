<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Non-nullable foreign keys: these relations are always present.
 *
 * @property-read Product $product
 * @property-read User $user
 */
#[Fillable(['product_id', 'user_id', 'rating', 'title', 'body', 'approved_at', 'merchant_reply', 'merchant_replied_at', 'helpful_count'])]
class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory, LogsActivity;

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'approved_at' => 'datetime',
            'merchant_replied_at' => 'datetime',
            'helpful_count' => 'integer',
        ];
    }

    /** @return HasMany<ReviewVote, $this> */
    public function votes(): HasMany
    {
        return $this->hasMany(ReviewVote::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<Review>  $query
     * @return Builder<Review>
     */
    #[Scope]
    protected function approved(Builder $query): Builder
    {
        return $query->whereNotNull('approved_at');
    }
}
