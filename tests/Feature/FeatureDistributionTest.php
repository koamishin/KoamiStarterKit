<?php

use App\Features\FeatureManifest;
use App\Filament\Resources\FeatureFlag\Pages\ListFeatureFlags;
use App\Models\FeatureFlag;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

test('feature route middleware enforces backend access and fails closed', function (): void {
    Route::get('/test-managed-feature', fn (): string => 'Enabled')->middleware(['web', 'feature:managed-route']);
    $flag = FeatureFlag::factory()->create(['key' => 'managed-route', 'enabled' => false]);
    $this->get('/test-managed-feature')->assertNotFound();
    $flag->update(['enabled' => true]);
    $this->get('/test-managed-feature')->assertOk();
});

test('frontend receives evaluated booleans not targeting definitions', function (): void {
    $user = User::factory()->create();
    FeatureFlag::factory()->create(['key' => 'beta-search', 'stage' => 'beta', 'rules' => [['attribute' => 'email_domain', 'values' => ['internal.example']]]]);
    $this->actingAs($user)->get(route('profile.edit'))->assertInertia(fn (AssertableInertia $assertableInertia): AssertableInertia => $assertableInertia
        ->where('featureFlags.beta-search', false)->missing('featureFlags.rules'));
});

test('cli export imports new definitions and preserves existing settings by default', function (): void {
    FeatureFlag::factory()->create(['key' => 'portable-search']);
    $directory = '/tmp/opencode/feature-manifest-'.Str::random(8);
    File::ensureDirectoryExists($directory);
    try {
        $path = $directory.'/features.json';
        $this->artisan('features:manifest', ['operation' => 'export', 'path' => $path])->assertSuccessful();
        $manifest = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);
        $manifest['features'][0]['key'] = 'imported-search';
        File::put($path, json_encode($manifest, JSON_THROW_ON_ERROR));
        $this->artisan('features:manifest', ['operation' => 'import', 'path' => $path])->assertSuccessful();
        expect(FeatureFlag::query()->where('key', 'imported-search')->exists())->toBeTrue();
    } finally {
        File::deleteDirectory($directory);
    }
});

test('admin workflows support overrides history rollback and release promotion', function (): void {
    $role = Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    $admin = User::factory()->create();
    $admin->assignRole($role);
    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $flag = FeatureFlag::factory()->create(['key' => 'admin-workflow', 'release_version' => 'v2', 'stage' => 'beta']);
    Livewire::test(ListFeatureFlags::class)
        ->callAction(TestAction::make('override')->table($flag), data: ['user_id' => $admin->id, 'decision' => 'on'])
        ->assertHasNoActionErrors();
    expect($flag->assignments()->where('kind', 'override')->count())->toBe(1);
    Livewire::test(ListFeatureFlags::class)
        ->callAction(TestAction::make('override')->table($flag), data: ['user_id' => $admin->id, 'decision' => 'inherit'])
        ->assertHasNoActionErrors();
    expect($flag->assignments()->count())->toBe(0);
    Livewire::test(ListFeatureFlags::class)->callAction(TestAction::make('history')->table($flag))->assertHasNoActionErrors();
    $flag->update(['enabled' => false]);
    $model = Activity::query()->where('subject_type', $flag->getMorphClass())->where('subject_id', $flag->id)->where('event', 'updated')->latest('id')->firstOrFail();
    Livewire::test(ListFeatureFlags::class)
        ->callAction(TestAction::make('rollback')->table($flag), data: ['revision' => $model->id])->assertHasNoActionErrors();
    expect($flag->fresh()->enabled)->toBeTrue();
    Livewire::test(ListFeatureFlags::class)
        ->callAction(TestAction::make('release')->table(), data: ['version' => 'v2'])->assertHasNoActionErrors();
    expect($flag->fresh()->stage->value)->toBe('stable');
});

test('manifest rejects duplicate keys unknown rules and inverted schedules', function (string $kind): void {
    FeatureFlag::factory()->create();
    $manifest = FeatureManifest::export();
    if ($kind === 'duplicate') {
        $manifest['features'][] = $manifest['features'][0];
    } elseif ($kind === 'rule') {
        $manifest['features'][0]['rules'] = [['attribute' => 'password', 'values' => ['bad']]];
    } else {
        $manifest['features'][0]['starts_at'] = '2027-01-02';
        $manifest['features'][0]['ends_at'] = '2027-01-01';
    }
    expect(fn (): int => FeatureManifest::import($manifest, true))->toThrow(ValidationException::class);
})->with(['duplicate', 'rule', 'schedule']);
