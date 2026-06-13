<?php

declare(strict_types=1);

namespace Modules\Blog\Filament\Resources\PostResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Blog\Filament\Resources\PostResource;

class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;
}
