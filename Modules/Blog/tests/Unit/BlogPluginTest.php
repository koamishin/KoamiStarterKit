<?php

use Modules\Blog\BlogPlugin;
use Tests\TestCase;

uses(TestCase::class);

test('blog plugin has a stable id', function (): void {
    expect((new BlogPlugin)->getId())->toBe('blog');
});

test('blog plugin static make returns a plugin instance', function (): void {
    expect(BlogPlugin::make())->toBeInstanceOf(BlogPlugin::class);
});
