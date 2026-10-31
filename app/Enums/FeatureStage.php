<?php

declare(strict_types=1);

namespace App\Enums;

enum FeatureStage: string
{
    case Draft = 'draft';
    case Beta = 'beta';
    case Stable = 'stable';
    case Archived = 'archived';

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $stage): array => [$stage->value => ucfirst($stage->value)])->all();
    }
}
