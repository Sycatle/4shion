<x-layouts.shop :title="$post->title" :description="$post->excerpt" og-type="article">
    <x-seo.json-ld :data="[
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $post->title,
        'datePublished' => $post->published_at->toAtomString(),
        'dateModified' => $post->updated_at->toAtomString(),
        'publisher' => ['@type' => 'Organization', 'name' => config('app.name')],
    ]" />

    <article class="shell py-10">
        <a href="{{ route('journal.index') }}" wire:navigate class="text-sm underline underline-offset-4">Journal</a>
        <h1 class="display mt-4 text-5xl sm:text-7xl">{{ $post->title }}</h1>
        <p class="mt-3 text-xs text-muted">{{ $post->published_at->translatedFormat('j F Y') }}</p>

        <div class="mt-8 max-w-2xl space-y-4 text-base leading-relaxed [&_a]:underline [&_h2]:mt-8 [&_h2]:text-3xl [&_h3]:mt-6 [&_h3]:text-2xl [&_li]:ml-5 [&_li]:list-disc">
            {{ $post->html() }}
        </div>
    </article>
</x-layouts.shop>
