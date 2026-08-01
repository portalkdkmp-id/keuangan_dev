<?php

use App\Enums\DirectorReviewStatus;
use App\Enums\SubmissionStatus;
use App\Models\Cooperative;
use App\Models\FinancialSubmission;
use App\Models\SubmissionRequestCategory;
use App\Models\SubmissionRequestType;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SubmissionCategorySeeder;
use Database\Seeders\SubmissionRequestMasterSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SubmissionCategorySeeder::class);
    $this->seed(SubmissionRequestMasterSeeder::class);
    Storage::fake('local');
});

function p5SetupDirectorSubmission(): array
{
    $pic = User::factory()->create();
    $pic->assignRole('pic_kdkmp');
    $cooperative = Cooperative::factory()->create();
    $cooperative->pics()->attach($pic->id, ['assigned_by' => null, 'assigned_at' => now(), 'is_primary' => true]);
    $account = $pic->bankAccounts()->create(['bank_name' => 'Bank Tujuan', 'account_number' => '9876543210', 'account_holder_name' => $pic->name, 'is_active' => true, 'is_primary' => true]);

    $staff = User::factory()->create();
    $staff->assignRole('finance_staff');
    $approver = User::factory()->create();
    $approver->assignRole('finance_approver');
    $director = User::factory()->create();
    $director->assignRole('finance_director');

    $payload = [
        'title' => 'Pengajuan Phase 5',
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
    test()->actingAs($approver)->post(route('approval.submissions.start-review', $submission));
    test()->actingAs($approver)->post(route('approval.submissions.approve', $submission), [
        'approved_amount' => 450000,
        'notes' => 'Disetujui approval.',
    ]);

    return [$pic, $staff, $approver, $director, $submission->refresh()];
}

test('finance approver approval creates pending director review', function () {
    [, , , $director, $submission] = p5SetupDirectorSubmission();

    expect($submission->status)->toBe(SubmissionStatus::DIRECTOR_REVIEW)
        ->and($submission->directorReviews()->count())->toBe(1)
        ->and($submission->directorReviews()->first()->status)->toBe(DirectorReviewStatus::PENDING);

    $this->actingAs($director)->get(route('director.submissions.index'))->assertOk();
    $this->actingAs($director)->get(route('director.submissions.show', $submission))->assertOk();
});

test('director can approve pending disbursement then disburse with proof', function () {
    [, , , $director, $submission] = p5SetupDirectorSubmission();

    $this->actingAs($director)->post(route('director.submissions.start-review', $submission))->assertRedirect();
    $this->actingAs($director)->post(route('director.submissions.approve-pending-disbursement', $submission), [
        'approved_amount' => 400000,
        'notes' => 'Disetujui lebih kecil oleh Director.',
    ])->assertRedirect();

    $submission->refresh();
    expect($submission->status)->toBe(SubmissionStatus::PENDING_DISBURSEMENT)
        ->and($submission->director_approved_amount)->toBe('400000.00')
        ->and($submission->disbursement_status)->toBe('pending');

    $this->actingAs($director)->post(route('director.submissions.disburse', $submission), [
        'transfer_date' => now()->toDateString(),
        'transferred_at' => now()->format('Y-m-d H:i:s'),
        'payment_method' => 'bank_transfer',
        'bank_name' => 'Bank Sumber',
        'source_account_name' => 'KDKMP',
        'source_account_number' => '1234567890',
        'transaction_reference' => 'TRX-001',
        'notes' => 'Dana dikirim.',
        'attachments' => [UploadedFile::fake()->image('transfer.jpg')],
    ])->assertRedirect();

    $submission->refresh();
    expect($submission->status)->toBe(SubmissionStatus::FUND_DISBURSED)
        ->and($submission->disbursement_status)->toBe('completed')
        ->and($submission->disbursement)->not->toBeNull()
        ->and($submission->disbursement->amount)->toBe('400000.00')
        ->and($submission->disbursement->attachments()->count())->toBe(1);
});

test('director can request revision and approver can resubmit to director', function () {
    [, , $approver, $director, $submission] = p5SetupDirectorSubmission();

    $this->actingAs($director)->post(route('director.submissions.start-review', $submission));
    $this->actingAs($director)->post(route('director.submissions.request-revision', $submission), [
        'revision_subject' => 'Perbaiki nominal approval',
        'revision_message' => 'Mohon review ulang nominal approval.',
        'revision_fields' => ['approval_amount', 'approval_notes'],
        'notes' => 'Catatan internal director.',
    ])->assertRedirect();

    $submission->refresh();
    expect($submission->status)->toBe(SubmissionStatus::DIRECTOR_REVISION_REQUESTED)
        ->and($submission->director_revision_count)->toBe(1);

    $this->actingAs($approver)->get(route('approval.director-revisions.index'))->assertOk();
    $this->actingAs($approver)->post(route('approval.director-revisions.resubmit', $submission), [
        'change_summary' => 'Nominal approval sudah dicek ulang.',
        'notes' => 'Mohon review ulang.',
    ])->assertRedirect();

    $submission->refresh();
    expect($submission->status)->toBe(SubmissionStatus::DIRECTOR_REVIEW)
        ->and($submission->directorReviews()->count())->toBe(2);
});
