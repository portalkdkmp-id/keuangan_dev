<?php

use App\Models\FinancialSubmission;
use App\Models\SubmissionStatusHistory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('pic report dashboard only contains submissions created by that pic', function () {
    $pic = User::factory()->create();
    $pic->assignRole('pic_kdkmp');
    $otherPic = User::factory()->create();
    $otherPic->assignRole('pic_kdkmp');
    $own = FinancialSubmission::factory()->create(['submitted_by' => $pic->id, 'title' => 'Milik PIC']);
    $other = FinancialSubmission::factory()->create(['submitted_by' => $otherPic->id, 'title' => 'Milik PIC Lain']);

    $this->actingAs($pic)
        ->get(route('submission-reports.index', ['pic_id' => $otherPic->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Reports/SubmissionExports/Index')
            ->where('isPic', true)
            ->has('submissions.data', 1)
            ->where('submissions.data.0.id', $own->id)
            ->missing('submissions.data.1'));

    $this->actingAs($pic)->get(route('submission-reports.single', $own))->assertOk();
    $this->actingAs($pic)->get(route('submission-reports.single', $other))->assertForbidden();
});

test('management report filters by pic and last status update date', function () {
    $staff = User::factory()->create();
    $staff->assignRole('finance_staff');
    $pic = User::factory()->create();
    $pic->assignRole('pic_kdkmp');
    $submission = FinancialSubmission::factory()->create(['submitted_by' => $pic->id]);
    SubmissionStatusHistory::factory()->create([
        'financial_submission_id' => $submission->id,
        'created_at' => '2026-08-10 12:00:00',
    ]);
    FinancialSubmission::factory()->create();

    $this->actingAs($staff)
        ->get(route('submission-reports.index', [
            'pic_id' => $pic->id,
            'status_updated_from' => '2026-08-10',
            'status_updated_to' => '2026-08-10',
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('submissions.data', 1)
            ->where('submissions.data.0.id', $submission->id));
});
