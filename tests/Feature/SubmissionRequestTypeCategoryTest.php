<?php

use App\Models\SubmissionRequestCategory;
use App\Models\SubmissionRequestType;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('request category has many nullable request types', function () {
    $category = SubmissionRequestCategory::create(['name' => 'Kategori A', 'slug' => 'kategori-a', 'is_active' => true]);
    $related = SubmissionRequestType::create(['name' => 'Jenis A', 'slug' => 'jenis-a', 'submission_request_category_id' => $category->id, 'is_active' => true]);
    $global = SubmissionRequestType::create(['name' => 'Jenis Global', 'slug' => 'jenis-global', 'submission_request_category_id' => null, 'is_active' => true]);

    expect($category->requestTypes)->toHaveCount(1)
        ->and($category->requestTypes->first()->is($related))->toBeTrue()
        ->and($global->requestCategory)->toBeNull();
});

test('request type master can assign or clear its category', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $category = SubmissionRequestCategory::create(['name' => 'Kategori B', 'slug' => 'kategori-b', 'is_active' => true]);

    $this->actingAs($admin)->post(route('submission-types.store'), [
        'name' => 'Jenis B',
        'submission_request_category_id' => $category->id,
        'sort_order' => 1,
        'is_active' => true,
    ])->assertSessionHasNoErrors();

    $type = SubmissionRequestType::where('name', 'Jenis B')->firstOrFail();
    expect($type->submission_request_category_id)->toBe($category->id);

    $this->actingAs($admin)->put(route('submission-types.update', $type), [
        'name' => 'Jenis B',
        'submission_request_category_id' => null,
        'sort_order' => 1,
        'is_active' => true,
    ])->assertSessionHasNoErrors();

    expect($type->refresh()->submission_request_category_id)->toBeNull();
});

test('submission rejects a request type from another category', function () {
    $staff = User::factory()->create();
    $staff->assignRole('finance_staff');
    $account = $staff->bankAccounts()->create(['bank_name' => 'Bank Test', 'account_number' => '123456', 'account_holder_name' => $staff->name, 'is_active' => true]);
    $selectedCategory = SubmissionRequestCategory::create(['name' => 'Kategori Dipilih', 'slug' => 'kategori-dipilih', 'is_active' => true]);
    $otherCategory = SubmissionRequestCategory::create(['name' => 'Kategori Lain', 'slug' => 'kategori-lain', 'is_active' => true]);
    $wrongType = SubmissionRequestType::create(['name' => 'Jenis Kategori Lain', 'slug' => 'jenis-kategori-lain', 'submission_request_category_id' => $otherCategory->id, 'is_active' => true]);

    $this->actingAs($staff)->post(route('submissions.store'), [
        'submission_request_category_id' => $selectedCategory->id,
        'recipient_bank_account_id' => $account->id,
        'title' => 'Pengajuan dengan jenis tidak sesuai',
        'needed_date' => now()->addDays(7)->toDateString(),
        'items' => [[
            'name' => 'Item pengajuan',
            'request_type_id' => $wrongType->id,
            'amount' => 100000,
        ]],
    ])->assertSessionHasErrors('items.0.request_type_id');
});

test('internal category clears cooperative and calculates urgency from needed date', function () {
    $pic = User::factory()->create();
    $pic->assignRole('pic_kdkmp');
    $account = $pic->bankAccounts()->create(['bank_name' => 'Bank PIC', 'account_number' => '987654', 'account_holder_name' => $pic->name, 'is_active' => true]);
    $category = SubmissionRequestCategory::create(['name' => 'Internal Kantor', 'slug' => 'internal-kantor', 'is_internal' => true, 'is_active' => true]);
    $type = SubmissionRequestType::create(['name' => 'ATK Internal', 'slug' => 'atk-internal', 'submission_request_category_id' => $category->id, 'is_active' => true]);

    $payload = [
        'submission_request_category_id' => $category->id,
        'cooperative_id' => '019ff535-b5f5-70cc-b882-38f44ff8bd7a',
        'recipient_bank_account_id' => $account->id,
        'title' => 'Kebutuhan internal kantor',
        'needed_date' => today()->addDays(7)->toDateString(),
        'items' => [['name' => 'Kertas A4', 'request_type_id' => $type->id, 'amount' => 100000]],
    ];

    $this->actingAs($pic)->post(route('submissions.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();

    $submission = $pic->submittedSubmissions()->firstOrFail();
    expect($submission->cooperative_id)->toBeNull()
        ->and($submission->is_urgent)->toBeTrue();

    $payload['title'] = 'Tanggal terlalu dekat';
    $payload['needed_date'] = today()->addDays(6)->toDateString();
    $this->actingAs($pic)->post(route('submissions.store'), $payload)->assertSessionHasErrors('needed_date');
});
