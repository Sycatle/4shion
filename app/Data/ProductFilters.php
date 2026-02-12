<?php

namespace App\Data;

final readonly class ProductFilters
{
    /**
     * @param  list<string>  $sizes
     * @param  list<string>  $colors
     */
    public function __construct(
        public string $search = '',
        public ?string $category = null,
        public array $sizes = [],
        public array $colors = [],
        public ?int $minPrice = null,
        public ?int $maxPrice = null,
        public string $sort = 'newest',
    ) {}
}
