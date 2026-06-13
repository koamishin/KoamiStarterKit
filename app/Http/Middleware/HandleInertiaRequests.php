<?php

namespace App\Http\Middleware;

use App\Enums\SocialLoginProvider;
use App\Features\FeatureRegistry;
use App\Settings\ApplicationFeaturesSettings;
use App\Settings\SocialLoginSettings;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        FeatureRegistry::initialize();

        $user = $request->user();
        $settingsFeatures = [];

        if ($user) {
            $settingsFeatures = [
                'profile' => FeatureRegistry::isFeatureAvailableForUser($user, 'settings_profile'),
                'security' => FeatureRegistry::isFeatureAvailableForUser($user, 'settings_mfa_app') || FeatureRegistry::isFeatureAvailableForUser($user, 'settings_mfa_email'),
                'password' => FeatureRegistry::isFeatureAvailableForUser($user, 'settings_password'),
                'appearance' => FeatureRegistry::isFeatureAvailableForUser($user, 'settings_appearance'),
                'passkeys' => FeatureRegistry::isFeatureAvailableForUser($user, 'settings_passkeys'),
            ];
        }

        $socialLoginSettings = app(SocialLoginSettings::class);

        $socialProviders = collect(SocialLoginProvider::cases())
            ->filter(fn (SocialLoginProvider $socialLoginProvider): bool => $socialLoginSettings->isProviderEnabled($socialLoginProvider))
            ->map(fn (SocialLoginProvider $socialLoginProvider): array => [
                'slug' => $socialLoginProvider->value,
                'label' => $socialLoginProvider->label(),
                'url' => route('auth.social.redirect', ['provider' => $socialLoginProvider->value]),
            ])
            ->values()
            ->all();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'impersonating' => app('impersonate')->isImpersonating(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'authLayout' => app(ApplicationFeaturesSettings::class)->auth_layout,
            'settingsFeatures' => $settingsFeatures,
            'socialProviders' => $socialProviders,
        ];
    }
}
