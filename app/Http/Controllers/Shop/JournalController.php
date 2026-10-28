<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Contracts\View\View;

final class JournalController extends Controller
{
    public function index(): View
    {
        return view('shop.journal.index', ['posts' => Post::query()->published()->latest('published_at')->paginate(9)]);
    }

    public function show(Post $post): View
    {
        abort_unless($post->isPublished(), 404);

        return view('shop.journal.show', ['post' => $post]);
    }
}
