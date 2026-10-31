<?php

namespace App\Models;

use App\Features\FeatureEvaluator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Pennant\Feature;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property string $feature
 * @property bool $active
 */
class RoleFeature extends Model
{
    use LogsActivity;

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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('feature-flags')->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $activity->properties = $activity->properties->put('feature_key', $this->feature);
    }

    protected $table = 'role_features';

    protected $fillable = [
        'role_id',
        'feature',
        'active',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
