<?php

declare(strict_types=1);

use App\Enums\RoleEnums;
use Database\Seeders\DatabaseSeeder;
use Spatie\Permission\Models\Role;

test('database seeder creates the default roles', function (): void {
    Role::query()->delete();

    $this->seed(DatabaseSeeder::class);

    $roles = Role::query()->pluck('name')->all();

    foreach (RoleEnums::values() as $role) {
        expect($roles)->toContain($role);
    }

    expect(Role::query()->where('name', RoleEnums::USER->value)->where('guard_name', 'web')->exists())->toBeTrue();
});
