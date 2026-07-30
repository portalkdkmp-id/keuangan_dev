<?php

use App\Models\Cooperative;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('super admin can create cooperative and invalid hierarchy is rejected', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $cooperative = Cooperative::factory()->make();

    $this->actingAs($admin)->post(route('cooperatives.store'), $cooperative->toArray())
        ->assertRedirect(route('cooperatives.index', absolute: false));

    $bad = Cooperative::factory()->make(['city_id' => Cooperative::factory()->create()->city_id]);
    $this->actingAs($admin)->post(route('cooperatives.store'), $bad->toArray())->assertSessionHasErrors('name');
});

test('cooperative access and pic assignment rules', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $pic = User::factory()->create();
    $pic->assignRole('pic_kdkmp');
    $otherPic = User::factory()->create();
    $otherPic->assignRole('pic_kdkmp');
    $finance = User::factory()->create();
    $finance->assignRole('finance_staff');
    $cooperative = Cooperative::factory()->create();
    $other = Cooperative::factory()->create();
    $pic->update(['city_id' => $cooperative->city_id]);
    $otherPic->update(['city_id' => $cooperative->city_id]);

    $this->actingAs($admin)->post(route('cooperatives.pics.store', $cooperative), ['user_id' => $pic->id, 'is_primary' => true])->assertRedirect();
    $this->actingAs($admin)->post(route('cooperatives.pics.store', $other, absolute: false), ['user_id' => $pic->id])->assertSessionHasErrors('user_id');
    $this->actingAs($admin)->post(route('cooperatives.pics.store', $cooperative), ['user_id' => $otherPic->id])->assertRedirect();
    $this->actingAs($admin)->post(route('cooperatives.pics.store', $cooperative), ['user_id' => $pic->id])->assertSessionHasErrors('user_id');

    expect($pic->assignedCooperatives()->count())->toBe(1)
        ->and($cooperative->pics()->count())->toBe(2)
        ->and($cooperative->pics()->wherePivot('is_primary', true)->count())->toBe(1);

    $this->actingAs($pic)->get(route('cooperatives.show', $cooperative))->assertOk();
    $this->actingAs($pic)->get(route('cooperatives.show', $other))->assertForbidden();
    $notAssigned = Cooperative::factory()->create();
    $this->actingAs($pic)->get(route('cooperatives.show', $notAssigned))->assertForbidden();
    $this->actingAs($finance)->get(route('cooperatives.show', $notAssigned))->assertOk();
});
