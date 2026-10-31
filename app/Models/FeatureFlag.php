<?php

namespace App\Models;

use App\Enums\FeatureStage;
use App\Features\FeatureEvaluator;
use Carbon\CarbonImmutable;
use Database\Factories\FeatureFlagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Pennant\Feature;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $key
 * @property string $name
 * @property string|null $description
 * @property FeatureStage $stage
 * @property bool $enabled
 * @property bool $default_value
 * @property bool $user_controllable
 * @property int $rollout_percentage
 * @property array<string>|null $environments
 * @property array<string>|null $target_roles
 * @property array<array{attribute: string, values: array<string>}>|null $rules
 * @property CarbonImmutable|null $starts_at
 * @property CarbonImmutable|null $ends_at
 */
class FeatureFlag extends Model
{
    /** @use HasFactory<FeatureFlagFactory> */
    use HasFactory;

    use LogsActivity;

    protected $fillable = [
        'key', 'name', 'description', 'stage', 'enabled', 'default_value',
        'user_controllable', 'rollout_percentage', 'environments', 'target_roles',
        'rules', 'tags', 'owner', 'release_version', 'starts_at', 'ends_at',
    ];

    protected $attributes = [
        'stage' => 'draft', 'enabled' => false, 'default_value' => false,
        'user_controllable' => false, 'rollout_percentage' => 100,
    ];

    #[\Override]
    protected static function booted(): void
    {
        static::saved(function (): void {
            app(FeatureEvaluator::class)->flush();
            Feature::flushCache();
        });
        static::deleted(function (): void {
            app(FeatureEvaluator::class)->flush();
            Feature::flushCache();
        });
    }

    #[\Override]
    protected function casts(): array
    {
        return [
            'stage' => FeatureStage::class,
            'enabled' => 'boolean', 'default_value' => 'boolean', 'user_controllable' => 'boolean',
            'rollout_percentage' => 'integer', 'environments' => 'array', 'target_roles' => 'array',
            'rules' => 'array', 'tags' => 'array', 'starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime',
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(FeatureAssignment::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('feature-flags')->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
