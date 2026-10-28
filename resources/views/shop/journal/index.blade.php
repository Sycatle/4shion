<x-layouts.shop title="Journal" description="Coulisses, matières et inspirations : le journal 4shion.">
    <div class="shell py-10">
        <h1 class="display text-6xl sm:text-8xl">Journal</h1>

        @forelse ($posts as $post)
            <article class="mt-10 max-w-2xl border-b border-line pb-8">
                <p class="text-xs text-muted">{{ $post->published_at->translatedFormat('j F Y') }}</p>
                <h2 class="mt-2 text-3xl"><a href="{{ route('journal.show', $post) }}" wire:navigate class="hover:underline hover:underline-offset-4">{{ $post->title }}</a></h2>
                @if ($post->excerpt)
                    <p class="mt-3 text-sm text-muted">{{ $post->excerpt }}</p>
                @endif
            </article>
        @empty
            <p class="mt-8 text-sm text-muted">Les premiers articles arrivent bientôt.</p>
        @endforelse

        <div class="mt-8">{{ $posts->links() }}</div>
    </div>
</x-layouts.shop>
