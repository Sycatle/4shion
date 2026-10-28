<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

final class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $xml = Cache::remember('sitemap.xml', now()->addHour(), fn (): string => view('seo.sitemap', [
            'staticUrls' => [route('home'), route('shop.index'), route('journal.index'), route('legal.terms'), route('legal.notice'), route('legal.privacy'), route('contact')],
            'categories' => Category::query()->orderBy('position')->get(['slug', 'updated_at']),
            'products' => Product::query()->active()->orderBy('id')->get(['slug', 'updated_at']),
            'posts' => Post::query()->published()->orderBy('id')->get(['slug', 'updated_at']),
        ])->render());

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
