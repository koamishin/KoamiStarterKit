<?php

declare(strict_types=1);

namespace Modules\Blog\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Blog\Models\Post;

class BlogDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Post::factory()->count(10)->create();
    }
}
