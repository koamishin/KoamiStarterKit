<?php

declare(strict_types=1);

namespace Modules\Blog\Filament\Resources\PostResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Blog\Filament\Resources\PostResource;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;
}
