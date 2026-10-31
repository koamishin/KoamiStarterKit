<?php

declare(strict_types=1);

namespace App\Features;

use App\Enums\FeatureStage;
use App\Models\FeatureAssignment;
use App\Models\FeatureFlag;
use App\Models\RoleFeature;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Pennant\Feature;

class FeatureEvaluator
{
    private ?Collection $flags = null;

    private array $userAssignments = [];

    private array $roleAssignments = [];

    private array $legacyPreferences = [];

    public function flush(): void
    {
        $this->flags = null;
        $this->userAssignments = [];
        $this->roleAssignments = [];
        $this->legacyPreferences = [];
    }

    /** @return array{active: bool, available: bool, reason: string, stage: string} */
    public function evaluate(string $key, ?User $user, bool $preferences = true): array
    {
        $this->flags ??= FeatureFlag::query()->get()->keyBy('key');
        $flag = $this->flags->get($key);
        $definition = FeatureRegistry::get($key);
        $stage = $flag->stage ?? FeatureStage::Stable;
        $decision = fn (bool $active, bool $available, string $reason): array => [
            'active' => $active, 'available' => $available, 'reason' => $reason, 'stage' => $stage->value,
        ];

        if (! $flag && ! $definition) {
            return $decision(false, false, 'unknown');
        }

        if ($flag) {
            if (! $flag->enabled || in_array($stage, [FeatureStage::Draft, FeatureStage::Archived], true)) {
                return $decision(false, false, 'disabled');
            }
            if ($flag->environments && ! in_array(app()->environment(), $flag->environments, true)) {
                return $decision(false, false, 'environment');
            }
            if (($flag->starts_at && now()->lt($flag->starts_at)) || ($flag->ends_at && now()->gte($flag->ends_at))) {
                return $decision(false, false, 'schedule');
            }
            if ($stage === FeatureStage::Beta && (! $user instanceof User || ! $user->beta_opted_in_at)) {
                return $decision(false, false, 'beta_opt_in_required');
            }
        }

        if ($key === 'beta-features') {
            return $decision($user?->beta_opted_in_at !== null, true, 'beta_enrollment');
        }

        if ($user) {
            $this->userAssignments[$user->id] ??= FeatureAssignment::query()->where('user_id', $user->id)->get()->groupBy('feature_flag_id');
            $this->roleAssignments[$user->id] ??= RoleFeature::query()->whereIn('role_id', $user->roles->modelKeys())->get()->groupBy('feature');
        }
        $assignments = $flag && $user instanceof User
            ? ($this->userAssignments[$user->id]->get($flag->id) ?? collect())->keyBy('kind')
            : collect();
        $override = $assignments->get('override');
        if ($override && ! $override->active) {
            return $decision(false, false, 'user_override');
        }

        $roleValues = $user ? ($this->roleAssignments[$user->id]->get($key) ?? collect())->pluck('active') : collect();
        if ($roleValues->contains(false)) {
            return $decision(false, false, 'role_denied');
        }

        if ($override) {
            return $decision(true, true, 'user_override');
        }

        if ($flag?->target_roles && (! $user instanceof User || ! $user->hasAnyRole($flag->target_roles))) {
            return $decision(false, false, 'audience');
        }
        foreach ($flag->rules ?? [] as $rule) {
            if (! $this->matches($rule, $user)) {
                return $decision(false, false, 'targeting_rule');
            }
        }
        if ($flag && $this->bucket($key, $user) >= $flag->rollout_percentage * 100) {
            return $decision(false, false, 'rollout');
        }

        $default = $flag->default_value ?? (bool) $definition?->get('default');
        $active = $roleValues->contains(true) ? true : $default;
        $reason = $roleValues->isNotEmpty() ? 'role' : 'default';
        $controllable = $flag->user_controllable ?? in_array($key, ['email-notifications', 'marketing-emails', 'activity-tracking'], true);

        if ($preferences && $user instanceof User && $controllable) {
            $preference = $assignments->get('preference');
            if ($preference) {
                return $decision($preference->active, true, 'preference');
            }
            $this->legacyPreferences[$user->id] ??= DB::table(config('pennant.stores.database.table', 'features'))
                ->where('scope', Feature::serializeScope($user))->pluck('value', 'name')->all();
            $legacy = $this->legacyPreferences[$user->id][$key] ?? null;
            if ($legacy !== null) {
                return $decision(json_decode($legacy, true) === true, true, 'legacy_preference');
            }
        }

        return $decision($active, true, $reason);
    }

    public function bucket(string $key, ?User $user): int
    {
        return hexdec(substr(hash('sha256', $key.':'.($user?->getKey() ?? 'guest')), 0, 8)) % 10000;
    }

    private function matches(array $rule, ?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return match ($rule['attribute'] ?? '') {
            'email_domain' => in_array(strtolower((string) str($user->email)->afterLast('@')), $rule['values'] ?? [], true),
            'user_id' => in_array((string) $user->id, array_map(strval(...), $rule['values'] ?? []), true),
            'verified' => $user->hasVerifiedEmail() === filter_var(($rule['values'][0] ?? false), FILTER_VALIDATE_BOOL),
            default => false,
        };
    }
}
