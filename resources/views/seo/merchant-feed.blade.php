{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
<channel>
    <title>{{ config('app.name') }}</title>
    <link>{{ url('/') }}</link>
    <description>Catalogue {{ config('app.name') }}</description>
@foreach ($variants as $variant)
    @php($product = $variant->product)
    <item>
        <g:id>{{ $variant->sku }}</g:id>
        <g:item_group_id>{{ $product->slug }}</g:item_group_id>
        <title>{{ $product->name }} — {{ $variant->label() }}</title>
        <description>{{ $product->description ?? $product->name }}</description>
        <link>{{ route('shop.show', $product) }}</link>
        @if ($product->imageUrl('card'))
        <g:image_link>{{ $product->imageUrl('card') }}</g:image_link>
        @endif
        <g:availability>{{ \App\Http\Controllers\Seo\MerchantFeedController::availability($variant) }}</g:availability>
        <g:price>{{ number_format($variant->regularPrice() / 100, 2, '.', '') }} {{ config('shop.currency') }}</g:price>
        @if ($variant->isOnSale())
        <g:sale_price>{{ number_format($variant->price() / 100, 2, '.', '') }} {{ config('shop.currency') }}</g:sale_price>
        @endif
        <g:brand>{{ config('app.name') }}</g:brand>
        <g:condition>new</g:condition>
        <g:identifier_exists>no</g:identifier_exists>
        <g:product_type>{{ $product->category->name }}</g:product_type>
        <g:color>{{ $variant->color }}</g:color>
        <g:size>{{ $variant->size }}</g:size>
    </item>
@endforeach
</channel>
</rss>
