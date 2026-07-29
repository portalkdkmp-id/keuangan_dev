<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('role permission seeder is idempotent and super admin has all permissions', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(RolePermissionSeeder::class);

    expect(Role::count())->toBe(5)
        ->and(Permission::count())->toBe(count(RolePermissionSeeder::PERMISSIONS));

    $user = User::factory()->create();
    $user->assignRole('super_admin');

    expect($user->getAllPermissions()->pluck('name')->sort()->values()->all())
        ->toBe(collect(RolePermissionSeeder::PERMISSIONS)->sort()->values()->all());
});
