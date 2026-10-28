<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;

final class HomeController extends Controller
{
    public function __invoke(): View
    {
        $featured = Product::query()->active()->withRating()->where('is_featured', true)->with(['category', 'media', 'variants'])->limit(4)->get();

        if ($featured->count() < 4) {
            $featured = $featured->concat(
                Product::query()->active()->withRating()->where('is_featured', false)->with(['category', 'media', 'variants'])->latest()->limit(4 - $featured->count())->get()
            );
        }

        return view('shop.home', [
            'featured' => $featured,
            'newest' => Product::query()->active()->withRating()->with(['category', 'media', 'variants'])->latest()->limit(8)->get(),
            'categories' => Category::query()->orderBy('position')->with(['products' => fn ($query) => $query->active()->with('media')->limit(2)])->get(),
        ]);
    }
}
