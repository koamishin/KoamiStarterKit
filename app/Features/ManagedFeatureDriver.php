<?php

declare(strict_types=1);

namespace App\Features;

use App\Models\FeatureAssignment;
use App\Models\User;
use Laravel\Pennant\Drivers\DatabaseDriver;

class ManagedFeatureDriver extends DatabaseDriver
{
    #[\Override]
    public function get(mixed $feature, mixed $scope): mixed
    {
        return app(FeatureEvaluator::class)->evaluate($feature, $scope instanceof User ? $scope : null)['active'];
    }

    #[\Override]
    public function getAll(mixed $features): array
    {
        $values = [];
        foreach ($features as $feature => $scopes) {
            $values[$feature] = array_map(fn ($scope): mixed => $this->get($feature, $scope), $scopes);
        }

        return $values;
    }

    #[\Override]
    public function set(mixed $feature, mixed $scope, mixed $value): void
    {
        if ($scope instanceof User) {
            $flag = FeatureManifest::ensure($feature);
            FeatureAssignment::query()->updateOrCreate([
                'feature_flag_id' => $flag->id, 'user_id' => $scope->id, 'kind' => 'override',
            ], ['active' => (bool) $value]);
        }
    }

    #[\Override]
    public function setForAllScopes(mixed $feature, mixed $value): void
    {
        FeatureManifest::ensure($feature)->update(['enabled' => (bool) $value, 'default_value' => (bool) $value]);
    }

    #[\Override]
    public function delete(mixed $feature, mixed $scope): void
    {
        if ($scope instanceof User) {
            FeatureAssignment::query()->whereHas('featureFlag', fn ($query) => $query->where('key', $feature))
                ->where('user_id', $scope->id)->get()->each->delete();
        }
    }

    #[\Override]
    public function setAll(array $features): void
    {
        foreach ($features as $feature) {
            $this->set($feature['feature'], $feature['scope'], $feature['value']);
        }
    }

    #[\Override]
    public function purge(mixed $features): void
    {
        $builder = FeatureAssignment::query()->where('kind', 'override');
        if ($features !== null) {
            $builder->whereHas('featureFlag', fn ($query) => $query->whereIn('key', $features));
        }
        $builder->get()->each->delete();
        parent::purge($features);
    }
}
