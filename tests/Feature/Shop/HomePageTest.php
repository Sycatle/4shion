<?php

declare(strict_types=1);

use App\Models\Product;

it('shows featured products and hides inactive ones', function (): void {
    $featured = Product::factory()->create(['is_featured' => true]);
    $inactive = Product::factory()->create(['is_active' => false, 'is_featured' => true]);

    $this->get('/')
        ->assertOk()
        ->assertSee($featured->name)
        ->assertDontSee($inactive->name);
});
