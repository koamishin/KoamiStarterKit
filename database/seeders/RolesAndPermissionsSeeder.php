<?php

namespace Database\Seeders;

use App\Enums\RoleEnums;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    protected array $permissionMap = [
        'viewAny' => 'ViewAny',
        'view' => 'View',
        'create' => 'Create',
        'update' => 'Update',
        'delete' => 'Delete',
        'restore' => 'Restore',
        'forceDelete' => 'ForceDelete',
        'forceDeleteAny' => 'ForceDeleteAny',
        'restoreAny' => 'RestoreAny',
        'replicate' => 'Replicate',
        'reorder' => 'Reorder',
    ];

    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = $this->discoverPermissionsFromPolicies();
        $this->createPermissions($permissions);

        $roles = $this->createRoles();
        $this->assignPermissionsToRoles($roles, $permissions);
        $this->createDefaultUsers($roles);
    }

    protected function discoverPermissionsFromPolicies(): array
    {
        $policiesPath = app_path('Policies');
        $permissions = [];

        if (! File::exists($policiesPath)) {
            return $permissions;
        }

        $policyFiles = File::files($policiesPath);

        foreach ($policyFiles as $file) {
            $policyName = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $policyName = preg_replace('/Policy$/', '', $policyName);

            if (empty($policyName)) {
                continue;
            }

            $content = $file->getContents();

            foreach ($this->permissionMap as $method => $permissionSuffix) {
                if (preg_match('/function\s+'.$method.'\s*\(/', $content)) {
                    $permissions[] = "{$permissionSuffix}:{$policyName}";
                }
            }
        }

        return $permissions;
    }

    protected function createPermissions(array $permissions): void
    {
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }

    protected function createRoles(): array
    {
        $roles = [];

        foreach (RoleEnums::cases() as $enumCase) {
            $role = Role::firstOrCreate([
                'name' => $enumCase->value,
                'guard_name' => 'web',
            ]);
            $roles[$enumCase->value] = $role;
        }

        return $roles;
    }

    protected function assignPermissionsToRoles(array $roles, array $permissions): void
    {
        $superAdminPermissions = Permission::all()->pluck('name')->toArray();
        $adminPermissions = array_filter($permissions, fn ($p) => ! str_contains($p, 'Role:'));
        $userPermissions = array_filter($permissions, fn ($p) => str_starts_with($p, 'ViewAny:'));

        $roles[RoleEnums::SUPER_ADMIN->value]->syncPermissions($superAdminPermissions);
        $roles[RoleEnums::ADMIN->value]->syncPermissions($adminPermissions);
        $roles[RoleEnums::USER->value]->syncPermissions($userPermissions);
    }

    protected function createDefaultUsers(array $roles): void
    {
        $admin = \App\Models\User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin User',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole($roles[RoleEnums::SUPER_ADMIN->value]);

        $user = \App\Models\User::firstOrCreate(
            ['email' => 'user@user.com'],
            [
                'name' => 'Regular User',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $user->assignRole($roles[RoleEnums::USER->value]);
    }
}
