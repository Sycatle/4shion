<?php

declare(strict_types=1);

namespace App\Catalog;

use App\Models\Product;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Collection;

final readonly class RecentlyViewed
{
    private const string KEY = 'recently_viewed';

    private const int LIMIT = 8;

    public function __construct(private Session $session, private Recommender $recommender) {}

    public function record(Product $product): void
    {
        $ids = array_values(array_filter($this->ids(), fn (int $id): bool => $id !== $product->id));
        array_unshift($ids, $product->id);

        $this->session->put(self::KEY, array_slice($ids, 0, self::LIMIT));
    }

    /** @return Collection<int, Product> */
    public function products(?Product $except = null, int $limit = 4): Collection
    {
        $ids = array_slice(array_values(array_filter($this->ids(), fn (int $id): bool => $id !== $except?->id)), 0, $limit);

        return $this->recommender->hydrate($ids);
    }

    /** @return list<int> */
    private function ids(): array
    {
        $stored = $this->session->get(self::KEY, []);

        return is_array($stored) ? array_values(array_map(intval(...), array_filter($stored, is_numeric(...)))) : [];
    }
}
