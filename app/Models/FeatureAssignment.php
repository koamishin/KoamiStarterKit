<?php

namespace App\Models;

use App\Features\FeatureEvaluator;
use Database\Factories\FeatureAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Pennant\Feature;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property bool $active
 * @property string $kind
 * @property FeatureFlag|null $featureFlag
 */
class FeatureAssignment extends Model
{
    /** @use HasFactory<FeatureAssignmentFactory> */
    use HasFactory;

    use LogsActivity;

    protected $fillable = ['feature_flag_id', 'user_id', 'kind', 'active'];

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
        return ['active' => 'boolean'];
    }

    public function featureFlag(): BelongsTo
    {
        return $this->belongsTo(FeatureFlag::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('feature-flags')->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $activity->properties = $activity->properties->put('feature_key', $this->featureFlag?->key);
    }
}
