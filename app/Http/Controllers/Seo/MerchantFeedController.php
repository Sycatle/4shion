<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/** Google Merchant Center product feed (RSS 2.0 with the g: namespace), one item per variant. */
final class MerchantFeedController extends Controller
{
    public function __invoke(): Response
    {
        $xml = Cache::remember('merchant-feed.xml', now()->addMinutes(30), fn (): string => view('seo.merchant-feed', [
            'variants' => ProductVariant::query()
                ->whereHas('product', fn ($products) => $products->where('is_active', true)->where('is_gift_card', false))
                ->with('product.category', 'product.media')
                ->orderBy('id')
                ->get(),
        ])->render());

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public static function availability(ProductVariant $variant): string
    {
        return match (true) {
            $variant->stock > 0 => 'in_stock',
            $variant->preorder_enabled => 'preorder',
            default => 'out_of_stock',
        };
    }
}
