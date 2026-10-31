<?php

declare(strict_types=1);

namespace App\Features;

use App\Enums\FeatureStage;
use App\Models\FeatureFlag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FeatureManifest
{
    public static function ensure(string $key): FeatureFlag
    {
        FeatureRegistry::initialize();
        $definition = FeatureRegistry::get($key);

        return FeatureFlag::query()->firstOrCreate(['key' => $key], [
            'name' => $definition?->get('name') ?? str($key)->headline()->toString(),
            'description' => $definition?->get('description'),
            'stage' => FeatureStage::Stable,
            'enabled' => true,
            'default_value' => (bool) $definition?->get('default'),
            'user_controllable' => in_array($key, ['email-notifications', 'marketing-emails', 'activity-tracking'], true),
        ]);
    }

    public static function sync(): void
    {
        FeatureRegistry::initialize();
        foreach (FeatureRegistry::keys() as $key) {
            static::ensure($key);
        }
    }

    public static function rules(string $prefix = ''): array
    {
        return collect([
            'key' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_.-]*$/'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'stage' => ['required', Rule::enum(FeatureStage::class)],
            'enabled' => ['required', 'boolean'],
            'default_value' => ['required', 'boolean'],
            'user_controllable' => ['required', 'boolean'],
            'rollout_percentage' => ['required', 'integer', 'between:0,100'],
            'environments' => ['nullable', 'array', 'max:20'],
            'environments.*' => ['string', 'max:80'],
            'target_roles' => ['nullable', 'array', 'max:100'],
            'target_roles.*' => ['string', 'max:100'],
            'rules' => ['nullable', 'array', 'max:20'],
            'rules.*' => ['array:attribute,values'],
            'rules.*.attribute' => ['required', Rule::in(['email_domain', 'user_id', 'verified'])],
            'rules.*.values' => ['required', 'array', 'min:1', 'max:100'],
            'rules.*.values.*' => ['required', 'string', 'max:255'],
            'tags' => ['nullable', 'array', 'max:20'],
            'tags.*' => ['string', 'max:80'],
            'owner' => ['nullable', 'string', 'max:255'],
            'release_version' => ['nullable', 'string', 'max:80'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
        ])->mapWithKeys(fn (array $rules, string $key): array => [$prefix.$key => $rules])->all();
    }

    public static function export(): array
    {
        return [
            'schema_version' => 1,
            'features' => FeatureFlag::all()->sortBy('key')->values()->map(
                fn (FeatureFlag $featureFlag): array => collect($featureFlag->toArray())->only((new FeatureFlag)->getFillable())->all()
            )->all(),
        ];
    }

    public static function import(array $manifest, bool $overwrite = false): int
    {
        $validator = Validator::make($manifest, [
            'schema_version' => ['required', 'integer', Rule::in([1])],
            'features' => ['required', 'array', 'max:1000'],
            ...static::rules('features.*.'),
            'features.*.key' => [...static::rules()['key'], 'distinct'],
        ]);
        $validator->after(function ($validator) use ($manifest): void {
            foreach (is_array($manifest['features'] ?? null) ? $manifest['features'] : [] as $index => $data) {
                if (is_array($data) && is_string($data['starts_at'] ?? null) && is_string($data['ends_at'] ?? null)
                    && strtotime($data['ends_at']) !== false && strtotime($data['starts_at']) !== false
                    && strtotime($data['ends_at']) <= strtotime($data['starts_at'])) {
                    $validator->errors()->add("features.{$index}.ends_at", 'End must be after start.');
                }
            }
        });
        $validated = $validator->validate();

        return DB::transaction(function () use ($validated, $overwrite): int {
            $count = 0;
            foreach ($validated['features'] as $data) {
                $data = array_intersect_key($data, array_flip((new FeatureFlag)->getFillable()));
                $flag = FeatureFlag::query()->firstOrNew(['key' => $data['key']]);
                if ($flag->exists && ! $overwrite) {
                    continue;
                }
                $flag->fill($data)->save();
                $count++;
            }

            return $count;
        });
    }

    public static function release(string $version): int
    {
        return DB::transaction(function () use ($version): int {
            $flagIds = FeatureFlag::query()->where('release_version', $version)->where('stage', FeatureStage::Beta)->lockForUpdate()->pluck('id')->all();
            $flags = FeatureFlag::query()->findMany($flagIds);
            foreach ($flags as $flag) {
                $flag->update(['stage' => FeatureStage::Stable]);
            }

            return $flags->count();
        });
    }
}
