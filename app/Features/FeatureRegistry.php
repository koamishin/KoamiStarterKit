<?php

namespace App\Features;

use App\Models\FeatureAssignment;
use App\Models\RoleFeature;
use App\Models\User;
use Illuminate\Support\Fluent;
use Laravel\Pennant\Feature;
use Spatie\Permission\Models\Role;

class FeatureRegistry
{
    protected static array $features = [];

    public static function register(string $key, string $name, string $description, bool $default = false): void
    {
        static::$features[$key] = new Fluent([
            'key' => $key,
            'name' => $name,
            'description' => $description,
            'default' => $default,
        ]);
    }

    public static function all(): array
    {
        return static::$features;
    }

    public static function get(string $key): ?Fluent
    {
        return static::$features[$key] ?? null;
    }

    public static function keys(): array
    {
        return array_keys(static::$features);
    }

    public static function options(): array
    {
        $options = [];
        foreach (static::$features as $key => $feature) {
            $options[$key] = $feature->name;
        }

        return $options;
    }

    public static function isEnabledForUser(?User $user, string $feature): bool
    {
        return app(FeatureEvaluator::class)->evaluate($feature, $user)['active'];
    }

    public static function toggleForUser(User $user, string $feature, bool $active): void
    {
        if ($active) {
            Feature::for($user)->activate($feature);
        } else {
            Feature::for($user)->deactivate($feature);
        }
    }

    public static function isEnabledForRole(Role $role, string $feature): bool
    {
        $roleFeature = RoleFeature::where('role_id', $role->id)
            ->where('feature', $feature)
            ->first();

        return $roleFeature?->active ?? static::get($feature)?->default ?? false;
    }

    public static function toggleForRole(Role $role, string $feature, bool $active): void
    {
        RoleFeature::updateOrCreate(
            ['role_id' => $role->id, 'feature' => $feature],
            ['active' => $active]
        );
    }

    public static function getFeaturesForRole(Role $role): array
    {
        $features = [];

        foreach (static::keys() as $key) {
            $features[$key] = static::isEnabledForRole($role, $key);
        }

        return $features;
    }

    public static function rolloutForAllUsers(string $feature, bool $active): int
    {
        FeatureManifest::ensure($feature)->update([
            'enabled' => $active, 'default_value' => $active, 'rollout_percentage' => 100,
        ]);

        return User::query()->count();
    }

    public static function clearUserOverrides(string $feature): int
    {
        $overrides = FeatureAssignment::query()->where('kind', 'override')
            ->whereHas('featureFlag', fn ($query) => $query->where('key', $feature))->get();
        $overrides->each->delete();
        Feature::flushCache();

        return $overrides->count();
    }

    public static function initialize(): void
    {
        static::register(
            'email-notifications',
            'Email Notifications',
            'Receive email notifications for important updates',
            true
        );

        static::register(
            'marketing-emails',
            'Marketing Emails',
            'Receive marketing and promotional emails',
            false
        );

        static::register(
            'activity-tracking',
            'Activity Tracking',
            'Allow tracking of your activity for analytics',
            true
        );

        static::register(
            'beta-features',
            'Beta Features',
            'Enable access to beta features and experimental functionality',
            false
        );

        static::registerSettingsFeatures();
    }

    public static function registerSettingsFeatures(): void
    {
        static::register(
            'settings_profile',
            'Profile Editing',
            'Allow editing profile information (name, email)',
            true
        );

        static::register(
            'settings_appearance',
            'Theme Customization',
            'Allow changing theme (light/dark/system)',
            true
        );

        static::register(
            'settings_password',
            'Password Changes',
            'Allow changing account password',
            true
        );

        static::register(
            'settings_mfa_app',
            'App MFA',
            'Allow enabling app-based two-factor authentication',
            true
        );

        static::register(
            'settings_mfa_email',
            'Email MFA',
            'Allow enabling email-based two-factor authentication',
            true
        );

        static::register(
            'settings_passkeys',
            'Passkeys',
            'Allow registering and signing in with passkeys (WebAuthn)',
            true
        );

        static::register(
            'settings_account_deletion',
            'Account Deletion',
            'Allow deleting account',
            false
        );
    }

    public static function isFeatureAvailableForUser(?User $user, string $feature): bool
    {
        return $user instanceof User && static::isEnabledForUser($user, $feature);
    }
}
