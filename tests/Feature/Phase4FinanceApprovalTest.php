<?php

use App\Enums\ApprovalReviewStatus;
use App\Enums\SubmissionStatus;
use App\Models\Cooperative;
use App\Models\FinancialSubmission;
use App\Models\SubmissionRequestCategory;
use App\Models\SubmissionRequestType;
use App\Models\User;
use App\Notifications\ApprovalRevisionRequestedNotification;
use App\Notifications\SubmissionForwardedToDirectorNotification;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SubmissionCategorySeeder;
use Database\Seeders\SubmissionRequestMasterSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SubmissionCategorySeeder::class);
    $this->seed(SubmissionRequestMasterSeeder::class);
    Storage::fake('local');
});

test('finance staff can forward approval revision to pic and manage attachments', function () {
    [$pic, $staff, $approver, , $submission] = p4SetupApprovalSubmission();
    $this->actingAs($approver)->post(route('approval.submissions.start-review', $submission));
    $this->actingAs($approver)->post(route('approval.submissions.request-revision', $submission), [
        'revision_subject' => 'Perbaiki dokumen',
        'revision_message' => 'Lampiran perlu diganti.',
        'revision_fields' => ['attachments'],
    ]);

    $this->actingAs($staff)->post(route('submissions.attachments.store', $submission), [
        'files' => [
            UploadedFile::fake()->image('revisi-1.jpg'),
            UploadedFile::fake()->image('revisi-2.jpg'),
        ],
    ])->assertRedirect()->assertSessionHasNoErrors();
    $this->actingAs($staff)->post(route('finance.approval-revisions.request-pic-revision', $submission), [
        'message' => 'Mohon PIC melengkapi dokumen sumber.',
    ])->assertRedirect();

    $submission->refresh();
    expect($submission->status)->toBe(SubmissionStatus::REVISION_REQUESTED)
        ->and($submission->attachments()->count())->toBe(2)
        ->and($submission->openRevisionRequest?->message)->toBe('Mohon PIC melengkapi dokumen sumber.');
    $this->actingAs($pic)->get(route('submissions.revision.edit', $submission))->assertOk();
});

function p4SetupApprovalSubmission(): array
{
    $pic = User::factory()->create();
    $pic->assignRole('pic_kdkmp');
    $cooperative = Cooperative::factory()->create();
    $cooperative->pics()->attach($pic->id, ['assigned_by' => null, 'assigned_at' => now(), 'is_primary' => true]);
    $account = $pic->bankAccounts()->create(['bank_name' => 'Bank Test', 'account_number' => '1234567890', 'account_holder_name' => $pic->name, 'is_active' => true, 'is_primary' => true]);

    $staff = User::factory()->create();
    $staff->assignRole('finance_staff');
    $approver = User::factory()->create();
    $approver->assignRole('finance_approver');
    $director = User::factory()->create();
    $director->assignRole('finance_director');

    $payload = [
        'title' => 'Pengajuan Phase 4',
        'cooperative_id' => $cooperative->id,
        'submission_request_category_id' => SubmissionRequestCategory::first()->id,
        'submission_request_type_id' => SubmissionRequestType::first()->id,
        'recipient_bank_account_id' => $account->id,
        'amount' => 500000,
        'needed_date' => now()->addDays(5)->toDateString(),
        'notes' => 'Catatan PIC',
    ];

    test()->actingAs($pic)->post(route('submissions.store'), $payload);
    $submission = FinancialSubmission::first();
    test()->actingAs($pic)->post(route('submissions.submit', $submission));
    test()->actingAs($staff)->post(route('finance.submissions.start-review', $submission));
    test()->actingAs($staff)->put(route('finance.submissions.finance-detail.update', $submission), [
        ...$payload,
        'finance_notes' => 'Review finance selesai.',
    ]);
    test()->actingAs($staff)->post(route('finance.submissions.forward-approval', $submission));

    return [$pic, $staff, $approver, $director, $submission->refresh()];
}

test('finance staff forward creates pending approval review and account snapshot', function () {
    [, , , , $submission] = p4SetupApprovalSubmission();

    expect($submission->status)->toBe(SubmissionStatus::APPROVAL_REVIEW)
        ->and($submission->bank_name_snapshot)->toBe('Bank Test')
        ->and($submission->approvalReviews()->count())->toBe(1)
        ->and($submission->latestApprovalReview->status)->toBe(ApprovalReviewStatus::PENDING);
});

test('approver can start review and approve to director queue', function () {
    Notification::fake();
    [, , $approver, $director, $submission] = p4SetupApprovalSubmission();

    $this->actingAs($approver)->post(route('approval.submissions.start-review', $submission))->assertRedirect();
    $this->actingAs($approver)->post(route('approval.submissions.approve', $submission), [
        'approved_amount' => 450000,
        'notes' => 'Disetujui dengan penyesuaian.',
    ])->assertRedirect();

    $submission->refresh();
    expect($submission->status)->toBe(SubmissionStatus::DIRECTOR_REVIEW)
        ->and($submission->approval_approved_amount)->toBe('450000.00')
        ->and($submission->latestApprovalReview->decision->value)->toBe('approved');
    Notification::assertSentTo($director, SubmissionForwardedToDirectorNotification::class);
    $this->actingAs($director)->get(route('director.submissions.index'))->assertOk();
    $this->actingAs($director)->get(route('director.submissions.show', $submission))->assertOk();
});

test('approver can request revision and finance staff can resubmit to approval', function () {
    Notification::fake();
    [, $staff, $approver, , $submission] = p4SetupApprovalSubmission();

    $this->actingAs($approver)->post(route('approval.submissions.start-review', $submission));
    $this->actingAs($approver)->post(route('approval.submissions.request-revision', $submission), [
        'revision_subject' => 'Perbaiki nominal',
        'revision_message' => 'Mohon perbaiki nominal review finance.',
        'revision_fields' => ['amount', 'finance_notes'],
        'notes' => null,
    ])->assertRedirect();

    $submission->refresh();
    expect($submission->status)->toBe(SubmissionStatus::APPROVAL_REVISION_REQUESTED)
        ->and($submission->approval_revision_count)->toBe(1);
    Notification::assertSentTo($staff, ApprovalRevisionRequestedNotification::class);

    $payload = [
        'title' => $submission->title,
        'submission_request_category_id' => $submission->submission_request_category_id,
        'submission_request_type_id' => $submission->submission_request_type_id,
        'amount' => 400000,
        'needed_date' => $submission->needed_date->toDateString(),
        'notes' => $submission->notes,
        'finance_notes' => 'Nominal sudah diperbaiki.',
    ];
    $this->actingAs($staff)->put(route('finance.approval-revisions.update', $submission), $payload)->assertRedirect();
    $this->actingAs($staff)->post(route('finance.approval-revisions.resubmit', $submission), [
        'change_summary' => 'Nominal review finance sudah diperbaiki.',
        'notes' => 'Mohon direview ulang.',
    ])->assertRedirect();

    $submission->refresh();
    expect($submission->status)->toBe(SubmissionStatus::APPROVAL_REVIEW)
        ->and($submission->approvalReviews()->count())->toBe(2)
        ->and($submission->latestApprovalReview->review_number)->toBe(2)
        ->and($submission->latestApprovalReview->submitted_amount)->toBe('400000.00');
});

test('approver rejection is final approval rejected status', function () {
    [, , $approver, , $submission] = p4SetupApprovalSubmission();

    $this->actingAs($approver)->post(route('approval.submissions.start-review', $submission));
    $this->actingAs($approver)->post(route('approval.submissions.reject', $submission), [
        'rejection_reason' => 'Dokumen pendukung tidak sesuai.',
        'notes' => null,
    ])->assertRedirect();

    $submission->refresh();
    expect($submission->status)->toBe(SubmissionStatus::APPROVAL_REJECTED)
        ->and($submission->latestApprovalReview->decision->value)->toBe('rejected');
});
