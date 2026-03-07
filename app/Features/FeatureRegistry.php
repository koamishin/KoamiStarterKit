<?php

namespace App\Features;

use App\Models\RoleFeature;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Fluent;
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

    public static function isEnabledForUser(?\App\Models\User $user, string $feature): bool
    {
        if (!$user instanceof \App\Models\User) {
            return static::get($feature)?->default ?? false;
        }

        $pennantValue = app(\Laravel\Pennant\FeatureManager::class)->value($feature);

        if ($pennantValue !== null) {
            return $pennantValue === true;
        }

        $userRoles = $user->roles()->pluck('roles.id');

        $roleFeature = RoleFeature::whereIn('role_id', $userRoles)
            ->where('feature', $feature)
            ->first();

        if ($roleFeature) {
            return $roleFeature->active;
        }

        return static::get($feature)?->default ?? false;
    }

    public static function toggleForUser(\App\Models\User $user, string $feature, bool $active): void
    {
        $featureManager = app(\Laravel\Pennant\FeatureManager::class);

        if ($active) {
            $featureManager->activateFor($user, $feature);
        } else {
            $featureManager->deactivateFor($user, $feature);
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
        $featureManager = app(\Laravel\Pennant\FeatureManager::class);

        $users = \App\Models\User::whereHas('roles')->get();
        $count = 0;

        foreach ($users as $user) {
            if ($active) {
                $featureManager->activateFor($user, $feature);
            } else {
                $featureManager->deactivateFor($user, $feature);
            }
            $count++;
        }

        return $count;
    }

    public static function clearUserOverrides(string $feature): int
    {
        return DB::table('features')
            ->where('name', $feature)
            ->delete();
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
            'settings_account_deletion',
            'Account Deletion',
            'Allow deleting account',
            false
        );
    }

    public static function isFeatureAvailableForUser($user, string $feature): bool
    {
        if (! $user) {
            return false;
        }

        $userRoles = $user->roles()->pluck('roles.id');

        $roleFeature = RoleFeature::whereIn('role_id', $userRoles)
            ->where('feature', $feature)
            ->first();

        if ($roleFeature) {
            return $roleFeature->active;
        }

        return static::get($feature)?->default ?? false;
    }
}
