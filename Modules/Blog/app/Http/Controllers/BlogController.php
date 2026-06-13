<?php

declare(strict_types=1);

namespace Modules\Blog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use stdClass;

class BlogController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('PostIndex', [
            'posts' => DB::table('posts')
                ->latest()
                ->limit(20)
                ->get(['id', 'title', 'slug', 'created_at'])
                ->map(fn (stdClass $post): array => [
                    'id' => (int) $post->id,
                    'title' => (string) $post->title,
                    'slug' => (string) $post->slug,
                    'created_at' => is_string($post->created_at) ? $post->created_at : null,
                ]),
        ]);
    }
}
