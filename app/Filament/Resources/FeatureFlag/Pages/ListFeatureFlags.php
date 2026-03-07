<?php

namespace App\Filament\Resources\FeatureFlag\Pages;

use App\Filament\Resources\FeatureFlag\FeatureFlagResource;
use Filament\Resources\Pages\ListRecords;

class ListFeatureFlags extends ListRecords
{
    protected static string $resource = FeatureFlagResource::class;
}
