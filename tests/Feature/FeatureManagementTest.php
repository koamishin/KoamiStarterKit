<?php

declare(strict_types=1);

use App\Enums\FeatureStage;
use App\Features\FeatureEvaluator;
use App\Features\FeatureManifest;
use App\Features\FeatureRegistry;
use App\Filament\Resources\FeatureFlag\Pages\ListFeatureFlags;
use App\Models\FeatureAssignment;
use App\Models\FeatureFlag;
use App\Models\RoleFeature;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;
use Laravel\Pennant\Feature;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

test('flags fail closed for unknown keys and preserve built in defaults', function (): void {
    $user = User::factory()->create();
    expect(FeatureRegistry::isEnabledForUser($user, 'unknown'))->toBeFalse()
        ->and(FeatureRegistry::isEnabledForUser($user, 'settings_profile'))->toBeTrue();
});

test('kill switch and lifecycle beat user overrides', function (string $stage, bool $enabled): void {
    $user = User::factory()->create(['beta_opted_in_at' => now()]);
    $flag = FeatureFlag::factory()->create(['stage' => $stage, 'enabled' => $enabled]);
    FeatureAssignment::factory()->create(['feature_flag_id' => $flag->id, 'user_id' => $user->id]);
    expect(FeatureRegistry::isEnabledForUser($user, $flag->key))->toBeFalse();
})->with([['draft', true], ['archived', true], ['stable', false]]);

test('beta access requires explicit opt in even with a forced on override', function (): void {
    $user = User::factory()->create();
    $flag = FeatureFlag::factory()->create(['stage' => 'beta']);
    FeatureAssignment::factory()->create(['feature_flag_id' => $flag->id, 'user_id' => $user->id]);
    expect(FeatureRegistry::isEnabledForUser($user, $flag->key))->toBeFalse();
    $user->forceFill(['beta_opted_in_at' => now()])->save();
    expect(FeatureRegistry::isEnabledForUser($user, $flag->key))->toBeTrue();
    $user->forceFill(['beta_opted_in_at' => null])->save();
    expect(FeatureRegistry::isEnabledForUser($user, $flag->key))->toBeFalse();
});

test('percentage rollout is deterministic and expanding cohorts retains users', function (): void {
    $users = User::factory()->count(30)->create();
    $flag = FeatureFlag::factory()->create(['rollout_percentage' => 20]);
    $first = $users->filter(fn (User $user): bool => FeatureRegistry::isEnabledForUser($user, $flag->key));
    $flag->update(['rollout_percentage' => 60]);
    foreach ($first as $user) {
        expect(FeatureRegistry::isEnabledForUser($user, $flag->key))->toBeTrue();
    }
    $featureEvaluator = app(FeatureEvaluator::class);
    foreach ($users as $user) {
        expect($featureEvaluator->bucket($flag->key, $user))->toBe($featureEvaluator->bucket($flag->key, $user));
    }
    $flag->update(['rollout_percentage' => 0]);
    expect(FeatureRegistry::isEnabledForUser($users->first(), $flag->key))->toBeFalse();
    $flag->update(['rollout_percentage' => 100]);
    expect(FeatureRegistry::isEnabledForUser($users->first(), $flag->key))->toBeTrue();
});

test('environment and schedule restrictions always apply', function (): void {
    $user = User::factory()->create();
    $flag = FeatureFlag::factory()->create(['environments' => ['production']]);
    expect(app(FeatureEvaluator::class)->evaluate($flag->key, $user)['reason'])->toBe('environment');
    $flag->update(['environments' => [], 'starts_at' => now()->addDay()]);
    expect(app(FeatureEvaluator::class)->evaluate($flag->key, $user)['reason'])->toBe('schedule');
    $flag->update(['starts_at' => now()->subDay(), 'ends_at' => now()]);
    expect(FeatureRegistry::isEnabledForUser($user, $flag->key))->toBeFalse();
});

test('targeting evaluates all rules and explicit role deny wins', function (): void {
    $role = Role::create(['name' => 'reviewer', 'guard_name' => 'web']);
    $user = User::factory()->create(['email' => 'person@example.com']);
    $user->assignRole($role);
    $flag = FeatureFlag::factory()->create([
        'target_roles' => ['reviewer'],
        'rules' => [['attribute' => 'email_domain', 'values' => ['example.com']], ['attribute' => 'verified', 'values' => ['true']]],
    ]);
    expect(FeatureRegistry::isEnabledForUser($user, $flag->key))->toBeTrue();
    $flag->update(['rules' => [['attribute' => 'email_domain', 'values' => ['other.com']]]]);
    expect(FeatureRegistry::isEnabledForUser($user, $flag->key))->toBeFalse();
    FeatureAssignment::factory()->create(['feature_flag_id' => $flag->id, 'user_id' => $user->id]);
    expect(FeatureRegistry::isEnabledForUser($user, $flag->key))->toBeTrue();
    RoleFeature::create(['role_id' => $role->id, 'feature' => $flag->key, 'active' => false]);
    expect(FeatureRegistry::isEnabledForUser($user, $flag->key))->toBeFalse();
});

test('pennant uses the same evaluator and scoped overrides do not affect other users', function (): void {
    $first = User::factory()->create();
    $second = User::factory()->create();
    $flag = FeatureFlag::factory()->create(['default_value' => false]);
    Feature::for($first)->activate($flag->key);
    expect(Feature::for($first)->active($flag->key))->toBeTrue()
        ->and(Feature::for($second)->active($flag->key))->toBeFalse();
    $flag->update(['enabled' => false]);
    expect(Feature::for($first)->active($flag->key))->toBeFalse();
});

test('pennant activation cannot bypass safety gates within the same request', function (): void {
    $user = User::factory()->create();
    $flag = FeatureFlag::factory()->create(['enabled' => false]);
    Feature::for($user)->activate($flag->key);
    expect(Feature::for($user)->active($flag->key))->toBeFalse();
    $flag->update(['enabled' => true, 'stage' => 'beta']);
    Feature::for($user)->activate($flag->key);
    expect(Feature::for($user)->active($flag->key))->toBeFalse();
    $user->forceFill(['beta_opted_in_at' => now()])->save();
    expect(Feature::for($user)->active($flag->key))->toBeTrue();
    $user->forceFill(['beta_opted_in_at' => null])->save();
    expect(Feature::for($user)->active($flag->key))->toBeFalse();
});

test('managed bulk writes purge and global updates respect the evaluator', function (): void {
    $user = User::factory()->create();
    $flag = FeatureFlag::factory()->create();
    Feature::for($user)->deactivate($flag->key);
    expect(Feature::for($user)->active($flag->key))->toBeFalse();
    Feature::purge([$flag->key]);
    expect(Feature::for($user)->active($flag->key))->toBeTrue();
    Feature::deactivateForEveryone($flag->key);
    expect(FeatureRegistry::isEnabledForUser($user, $flag->key))->toBeFalse();
});

test('deleted overrides remain in feature audit history', function (): void {
    $flag = FeatureFlag::factory()->create();
    $assignment = FeatureAssignment::factory()->create(['feature_flag_id' => $flag->id]);
    $assignment->delete();
    expect(Activity::query()->where('event', 'deleted')->where('properties->feature_key', $flag->key)->exists())->toBeTrue();
});

test('preferences cannot bypass eligibility or modify protected flags', function (): void {
    $user = User::factory()->create();
    $flag = FeatureFlag::factory()->create(['user_controllable' => true, 'stage' => 'beta']);
    $this->actingAs($user)->patch(route('features.update'), ['feature' => $flag->key, 'active' => true])->assertForbidden();
    $this->patch(route('features.update'), ['feature' => 'settings_profile', 'active' => false])->assertForbidden();
    $this->patch(route('features.update'), ['feature' => 'unknown', 'active' => true])->assertForbidden();
    expect(FeatureAssignment::query()->count())->toBe(0);
});

test('users can change preferences with proper inertia redirects and opt out without losing stable access', function (): void {
    $user = User::factory()->create();
    $flag = FeatureFlag::factory()->create(['user_controllable' => true]);
    $this->actingAs($user)->from(route('profile.edit'))->patch(route('profile.beta.update'), ['enabled' => true])->assertRedirect(route('profile.edit'));
    expect($user->fresh()->beta_opted_in_at)->not->toBeNull();
    $this->from(route('features.edit'))->patch(route('features.update'), ['feature' => $flag->key, 'active' => false])->assertRedirect(route('features.edit'));
    expect(FeatureRegistry::isEnabledForUser($user, $flag->key))->toBeFalse();
    $flag->update(['user_controllable' => false]);
    $this->patch(route('profile.beta.update'), ['enabled' => false])->assertRedirect();
    expect($user->fresh()->beta_opted_in_at)->toBeNull()
        ->and(FeatureRegistry::isEnabledForUser($user, $flag->key))->toBeTrue();
});

test('beta preference validates and guests cannot enroll', function (): void {
    $this->patch(route('profile.beta.update'), ['enabled' => true])->assertRedirect(route('login'));
    $user = User::factory()->create();
    $this->actingAs($user)->patch(route('profile.beta.update'), ['enabled' => 'bad'])->assertSessionHasErrors('enabled');
    $this->get(route('profile.edit'))->assertInertia(fn (AssertableInertia $assertableInertia): AssertableInertia => $assertableInertia->where('betaOptedIn', false));
});

test('manifest round trips only definitions and refuses accidental overwrite', function (): void {
    $flag = FeatureFlag::factory()->create(['name' => 'Original']);
    FeatureAssignment::factory()->create(['feature_flag_id' => $flag->id]);
    $manifest = FeatureManifest::export();
    expect(json_encode($manifest))->not->toContain('user_id')->not->toContain('assignments');
    $manifest['features'][0]['name'] = 'Changed';
    expect(FeatureManifest::import($manifest))->toBe(0)->and($flag->fresh()->name)->toBe('Original');
    expect(FeatureManifest::import($manifest, true))->toBe(1)->and($flag->fresh()->name)->toBe('Changed');
});

test('invalid manifest is rejected atomically', function (): void {
    $flag = FeatureFlag::factory()->create();
    $manifest = FeatureManifest::export();
    $manifest['features'][0]['rollout_percentage'] = 101;
    expect(fn (): int => FeatureManifest::import($manifest, true))->toThrow(ValidationException::class);
    expect($flag->fresh()->rollout_percentage)->toBe(100);
});

test('release promotion is exact audited and preserves kill switch and rollout', function (): void {
    $flag = FeatureFlag::factory()->create(['stage' => 'beta', 'release_version' => '2.0', 'enabled' => false, 'rollout_percentage' => 20]);
    $other = FeatureFlag::factory()->create(['stage' => 'beta', 'release_version' => '3.0']);
    $this->artisan('features:release', ['version' => '2.0'])->assertSuccessful();
    expect($flag->fresh()->stage)->toBe(FeatureStage::Stable)
        ->and($flag->fresh()->enabled)->toBeFalse()->and($flag->fresh()->rollout_percentage)->toBe(20)
        ->and($other->fresh()->stage)->toBe(FeatureStage::Beta);
    expect(Activity::query()->where('subject_id', $flag->id)->where('event', 'updated')->exists())->toBeTrue();
});

test('sync is idempotent and keeps managed settings', function (): void {
    FeatureManifest::sync();
    $count = FeatureFlag::query()->count();
    $featureFlag = FeatureFlag::query()->where('key', 'settings_profile')->firstOrFail();
    $featureFlag->update(['enabled' => false]);
    $this->artisan('features:manifest', ['operation' => 'sync'])->assertSuccessful();
    expect(FeatureFlag::query()->count())->toBe($count)->and($featureFlag->fresh()->enabled)->toBeFalse();
});

test('feature management rejects ordinary users', function (): void {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs(User::factory()->create());
    Livewire::test(ListFeatureFlags::class)->assertForbidden();
});

test('admin can create target preview promote pause and audit flags', function (): void {
    $role = Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    $admin = User::factory()->create();
    $admin->assignRole($role);
    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Livewire::test(ListFeatureFlags::class)->callAction(TestAction::make('create')->table(), data: [
        'key' => 'new-search', 'name' => 'New search', 'stage' => 'beta', 'enabled' => true,
        'default_value' => true, 'user_controllable' => true, 'rollout_percentage' => 100,
    ])->assertHasNoFormErrors();
    $featureFlag = FeatureFlag::query()->where('key', 'new-search')->firstOrFail();
    Livewire::test(ListFeatureFlags::class)->callAction(TestAction::make('promote')->table($featureFlag))->assertHasNoActionErrors();
    expect($featureFlag->fresh()->stage)->toBe(FeatureStage::Stable);
    Livewire::test(ListFeatureFlags::class)->callAction(TestAction::make('kill')->table($featureFlag))->assertHasNoActionErrors();
    expect($featureFlag->fresh()->enabled)->toBeFalse();
    Livewire::test(ListFeatureFlags::class)->callAction(TestAction::make('preview')->table($featureFlag), data: ['user_id' => $admin->id])->assertHasNoActionErrors();
});
