<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('shop.home', [
            'featured' => Product::query()->active()->where('is_featured', true)->with(['category', 'media', 'variants'])->limit(4)->get(),
            'newest' => Product::query()->active()->with(['category', 'media', 'variants'])->latest()->limit(8)->get(),
            'categories' => Category::query()->orderBy('position')->get(),
        ]);
    }
}
