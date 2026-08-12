<?php

use App\Models\City;
use App\Models\Cooperative;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('management roles can open pic list and bulk assignment page', function () {
    $city = City::factory()->create();
    $pic = User::factory()->create(['city_id' => $city->id]);
    $pic->assignRole('pic_kdkmp');
    Cooperative::factory()->create(['city_id' => $city->id]);

    foreach (['super_admin', 'finance_staff', 'finance_approver'] as $role) {
        $actor = User::factory()->create();
        $actor->assignRole($role);
        $this->actingAs($actor)->get(route('pics.index'))->assertOk();
        $this->actingAs($actor)->get(route('pics.assignments', $pic))->assertOk();
    }
});

test('bulk assignment only changes visible cooperatives inside pic city', function () {
    $staff = User::factory()->create();
    $staff->assignRole('finance_staff');
    $city = City::factory()->create();
    $otherCity = City::factory()->create();
    $pic = User::factory()->create(['city_id' => $city->id]);
    $pic->assignRole('pic_kdkmp');
    $local = Cooperative::factory()->create(['city_id' => $city->id]);
    $outside = Cooperative::factory()->create(['city_id' => $otherCity->id]);

    $this->actingAs($staff)->put(route('pics.assignments.sync', $pic), [
        'cooperative_ids' => [$local->id],
        'visible_cooperative_ids' => [$local->id],
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($pic->assignedCooperatives()->whereKey($local->id)->exists())->toBeTrue();

    $this->actingAs($staff)->put(route('pics.assignments.sync', $pic), [
        'cooperative_ids' => [$outside->id],
        'visible_cooperative_ids' => [$outside->id],
    ])->assertSessionHasErrors('cooperative_ids');
});

test('pic cannot access pic management', function () {
    $pic = User::factory()->create();
    $pic->assignRole('pic_kdkmp');

    $this->actingAs($pic)->get(route('pics.index'))->assertForbidden();
});
