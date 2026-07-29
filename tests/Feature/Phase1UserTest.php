<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('super admin can view and create users', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)->get(route('users.index'))->assertOk();

    $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'Finance User',
        'email' => 'finance@example.com',
        'password' => 'password',
        'role' => 'finance_staff',
        'is_active' => true,
    ])->assertRedirect(route('users.index', absolute: false));

    $this->assertDatabaseHas('users', ['email' => 'finance@example.com']);
});

test('user without permission cannot view users', function () {
    $user = User::factory()->create();
    $user->assignRole('finance_staff');

    $this->actingAs($user)->get(route('users.index'))->assertForbidden();
});

test('user cannot delete or deactivate themselves', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)->delete(route('users.destroy', $admin))->assertForbidden();

    $this->actingAs($admin)->put(route('users.update', $admin), [
        'name' => $admin->name,
        'email' => $admin->email,
        'password' => '',
        'role' => 'super_admin',
        'is_active' => false,
    ])->assertSessionHasErrors('is_active');
});

test('inactive user cannot login', function () {
    User::factory()->create(['email' => 'inactive@example.com', 'password' => 'password', 'is_active' => false]);

    $this->post(route('login.store'), ['email' => 'inactive@example.com', 'password' => 'password'])
        ->assertSessionHasErrors();

    $this->assertGuest();
});
