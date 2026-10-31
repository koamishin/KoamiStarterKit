<?php

declare(strict_types=1);

namespace App\Http\Controllers\Setup;

use App\Enums\RoleEnums;
use App\Http\Controllers\Controller;
use App\Http\Requests\Setup\SetupRequest;
use App\Models\User;
use App\Settings\ApplicationDetailsSettings;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Throwable;

class SetupController extends Controller
{
    public function create(): Response|RedirectResponse
    {
        if ($this->isAlreadyInstalled()) {
            return redirect()->route('home');
        }

        $dbConnected = false;
        try {
            DB::connection()->getPdo();
            $dbConnected = true;
        } catch (Throwable) {
            $dbConnected = false;
        }

        $requirements = [
            'php' => [
                'name' => 'PHP Version >= 8.3',
                'current' => PHP_VERSION,
                'satisfied' => version_compare(PHP_VERSION, '8.3.0', '>='),
            ],
            'pdo' => [
                'name' => 'PDO Extension',
                'current' => extension_loaded('pdo') ? 'Installed' : 'Missing',
                'satisfied' => extension_loaded('pdo'),
            ],
            'database_driver' => [
                'name' => 'Database Driver (SQLite, MySQL, or Postgres)',
                'current' => extension_loaded('pdo_sqlite') || extension_loaded('pdo_mysql') || extension_loaded('pdo_pgsql') ? 'Supported' : 'Missing',
                'satisfied' => extension_loaded('pdo_sqlite') || extension_loaded('pdo_mysql') || extension_loaded('pdo_pgsql'),
            ],
            'storage_writable' => [
                'name' => 'Storage Directory Writable',
                'current' => is_writable(storage_path()) ? 'Writable' : 'Not Writable',
                'satisfied' => is_writable(storage_path()),
            ],
            'cache_writable' => [
                'name' => 'Bootstrap Cache Writable',
                'current' => is_writable(base_path('bootstrap/cache')) ? 'Writable' : 'Not Writable',
                'satisfied' => is_writable(base_path('bootstrap/cache')),
            ],
            'database_connected' => [
                'name' => 'Database Connection',
                'current' => $dbConnected ? 'Connected' : 'Connection Failed',
                'satisfied' => $dbConnected,
            ],
        ];

        return Inertia::render('Setup', [
            'requirements' => $requirements,
            'defaultSiteName' => config('app.name', 'My Application'),
            'timezones' => DateTimeZone::listIdentifiers(DateTimeZone::ALL),
            'systemTimezone' => config('app.timezone', 'UTC'),
        ]);
    }

    public function store(SetupRequest $setupRequest): RedirectResponse
    {
        if ($this->isAlreadyInstalled()) {
            return redirect()->route('home');
        }

        $validated = $setupRequest->validated();

        // 1. Ensure migrations are up to date
        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('module:migrate', ['--force' => true, '--no-interaction' => true]);

        // 2. Ensure default roles exist
        $superAdminRole = Role::findOrCreate(RoleEnums::SUPER_ADMIN->value, 'web');
        Role::findOrCreate(RoleEnums::ADMIN->value, 'web');
        Role::findOrCreate(RoleEnums::USER->value, 'web');

        // 3. Create Super Admin User
        /** @var User $user */
        $user = User::query()->create([
            'name' => $validated['admin_name'],
            'email' => $validated['admin_email'],
            'password' => Hash::make($validated['admin_password']),
            'email_verified_at' => now(),
        ]);

        $user->assignRole($superAdminRole);

        // 4. Update Site Details Settings
        try {
            $settings = app(ApplicationDetailsSettings::class);
            $settings->site_name = $validated['site_name'];
            if (! empty($validated['site_description'])) {
                $settings->site_description = $validated['site_description'];
            }
            $settings->timezone = $validated['timezone'];
            $settings->save();
        } catch (Throwable) {
            // Silently fallback if settings are unconfigured
        }

        // 5. Sync Feature Flags
        try {
            Artisan::call('features:manifest', ['operation' => 'sync']);
        } catch (Throwable) {
            // Silently fallback
        }

        // 6. Write installation marker
        @file_put_contents(storage_path('installed'), now()->toIso8601String());

        // 7. Log in super admin and redirect
        Auth::login($user);

        return redirect('/admin');
    }

    protected function isAlreadyInstalled(): bool
    {
        if (file_exists(storage_path('installed'))) {
            return true;
        }

        try {
            return User::query()->whereHas('roles', fn ($q) => $q->where('name', RoleEnums::SUPER_ADMIN->value))->exists();
        } catch (Throwable) {
            return false;
        }
    }
}
