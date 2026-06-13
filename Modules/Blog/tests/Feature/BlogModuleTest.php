<?php

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\BlogPlugin;
use Modules\Blog\Filament\Resources\PostResource;
use Modules\Blog\Models\Post;
use Modules\Blog\Providers\BlogServiceProvider;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('post model can be created via factory', function (): void {
    $post = Post::factory()->create();

    expect($post)
        ->toBeInstanceOf(Post::class)
        ->and($post->title)->not->toBeEmpty()
        ->and($post->slug)->not->toBeEmpty();
});

test('post belongs to a user', function (): void {
    $post = Post::factory()->create();

    expect($post->user)
        ->not->toBeNull()
        ->toBeInstanceOf(User::class);
});

test('blog index route is registered and renders the inertia page', function (): void {
    Post::factory()->count(3)->create();

    $this->get('/blog')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('PostIndex')
            ->has('posts', 3)
        );
});

test('filament auto-discovers post resource on the admin panel', function (): void {
    $panel = Filament::getPanel('admin');

    $resources = collect($panel->getResources())
        ->values()
        ->all();

    expect($resources)->toContain(PostResource::class);
});

test('blog plugin returns its static id', function (): void {
    expect((new BlogPlugin)->getId())->toBe('blog');
});

test('module service provider is registered when enabled', function (): void {
    $loaded = Application::getInstance()->getLoadedProviders();

    expect($loaded)->toHaveKey(BlogServiceProvider::class);
});
