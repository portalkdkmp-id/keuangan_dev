<?php

use App\Enums\SubmissionStatus;
use App\Models\FinancialSubmission;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('pic only sees own final submissions in history', function () {
    $pic = User::factory()->create();
    $pic->assignRole('pic_kdkmp');
    $otherPic = User::factory()->create();
    $otherPic->assignRole('pic_kdkmp');

    $own = FinancialSubmission::factory()->create([
        'submitted_by' => $pic->id,
        'status' => SubmissionStatus::CANCELLED,
    ]);
    FinancialSubmission::factory()->create([
        'submitted_by' => $otherPic->id,
        'status' => SubmissionStatus::DIRECTOR_REJECTED,
    ]);
    FinancialSubmission::factory()->create([
        'submitted_by' => $pic->id,
        'status' => SubmissionStatus::SUBMITTED,
    ]);

    $this->actingAs($pic)->get(route('submission-history.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Submissions/History/Index')
            ->has('submissions.data', 1)
            ->where('submissions.data.0.id', $own->id));
});

test('finance approver can inspect final submission and revision history', function () {
    $approver = User::factory()->create();
    $approver->assignRole('finance_approver');
    $submission = FinancialSubmission::factory()->create([
        'status' => SubmissionStatus::APPROVAL_REJECTED,
    ]);

    $this->actingAs($approver)->get(route('submission-history.show', $submission))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Submissions/History/Show')
            ->where('submission.id', $submission->id)
            ->has('submission.revision_requests')
            ->has('submission.approval_reviews'));
});

test('active submission cannot be opened through history', function () {
    $staff = User::factory()->create();
    $staff->assignRole('finance_staff');
    $submission = FinancialSubmission::factory()->create([
        'status' => SubmissionStatus::FINANCE_REVIEW,
    ]);

    $this->actingAs($staff)->get(route('submission-history.show', $submission))->assertNotFound();
});
