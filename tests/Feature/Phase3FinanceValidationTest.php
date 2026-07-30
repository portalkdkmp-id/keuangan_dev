<?php

use App\Enums\SubmissionStatus;
use App\Models\Cooperative;
use App\Models\FinancialSubmission;
use App\Models\SubmissionCategory;
use App\Models\SubmissionRequestCategory;
use App\Models\SubmissionRequestType;
use App\Models\User;
use App\Notifications\SubmissionForwardedToApprovalNotification;
use App\Notifications\SubmissionResubmittedNotification;
use App\Notifications\SubmissionRevisionRequestedNotification;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SubmissionCategorySeeder;
use Database\Seeders\SubmissionRequestMasterSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SubmissionCategorySeeder::class);
    $this->seed(SubmissionRequestMasterSeeder::class);
});

function p3PicWithCooperative(): array
{
    $pic = User::factory()->create();
    $pic->assignRole('pic_kdkmp');
    $cooperative = Cooperative::factory()->create();
    $cooperative->pics()->attach($pic->id, ['assigned_by' => null, 'assigned_at' => now(), 'is_primary' => true]);
    $pic->bankAccounts()->create(['bank_name' => 'Bank Test', 'account_number' => '123', 'account_holder_name' => $pic->name, 'is_active' => true, 'is_primary' => true]);

    return [$pic, $cooperative];
}

function p3SubmissionPayload(Cooperative $cooperative): array
{
    return [
        'cooperative_id' => $cooperative->id,
        'submission_request_category_id' => SubmissionRequestCategory::first()->id,
        'submission_request_type_id' => SubmissionRequestType::first()->id,
        'recipient_bank_account_id' => User::role('pic_kdkmp')->first()?->bankAccounts()->first()?->id,
        'amount' => 300000,
        'title' => 'Dana validasi finance',
        'purpose' => 'Pembayaran kebutuhan operasional',
        'needed_date' => now()->addDays(3)->toDateString(),
        'notes' => null,
        'items' => [
            ['category_id' => SubmissionCategory::first()->id, 'description' => 'ATK kantor', 'quantity' => 2, 'unit' => 'paket', 'unit_price' => 150000, 'notes' => null],
        ],
    ];
}

function p3ReviewedSubmission(): array
{
    [$pic, $cooperative] = p3PicWithCooperative();
    $staff = User::factory()->create();
    $staff->assignRole('finance_staff');

    test()->actingAs($pic)->post(route('submissions.store'), p3SubmissionPayload($cooperative));
    $submission = FinancialSubmission::first();
    test()->actingAs($pic)->post(route('submissions.submit', $submission));
    test()->actingAs($staff)->post(route('finance.submissions.start-review', $submission));

    return [$pic, $staff, $submission->refresh(), $cooperative];
}

function p3FinanceDetailPayload(array $overrides = []): array
{
    return array_merge([
        'budget_account_code' => 'BA-001',
        'budget_account_name' => 'Operasional',
        'cost_center_code' => 'CC-001',
        'cost_center_name' => 'Kantor',
        'expense_group' => 'Office',
        'payment_method' => 'bank_transfer',
        'beneficiary_name' => 'Koperasi Tujuan',
        'beneficiary_bank' => 'Bank Mandiri',
        'beneficiary_account_number' => '1234567890',
        'beneficiary_account_holder' => 'Koperasi Tujuan',
        'tax_applicable' => false,
        'tax_notes' => null,
        'finance_notes' => null,
        'validated_total_amount' => 300000,
    ], $overrides);
}

test('finance can request revision and pic can resubmit to submitted queue', function () {
    Notification::fake();
    [$pic, $staff, $submission, $cooperative] = p3ReviewedSubmission();

    $this->actingAs($staff)->post(route('finance.submissions.request-revision', $submission), [
        'subject' => 'Lengkapi item',
        'message' => 'Mohon perjelas deskripsi item.',
        'fields' => ['items'],
    ])->assertRedirect();

    $submission->refresh();
    expect($submission->status)->toBe(SubmissionStatus::REVISION_REQUESTED)
        ->and($submission->revision_count)->toBe(1)
        ->and($submission->openRevisionRequest)->not->toBeNull();
    Notification::assertSentTo($pic, SubmissionRevisionRequestedNotification::class);

    $payload = p3SubmissionPayload($cooperative);
    $payload['title'] = 'Dana validasi finance revisi';
    $this->actingAs($pic)->put(route('submissions.revision.update', $submission), $payload)->assertRedirect();
    $this->actingAs($pic)->post(route('submissions.resubmit', $submission), ['message' => 'Sudah direvisi.'])->assertRedirect();

    $submission->refresh();
    expect($submission->status)->toBe(SubmissionStatus::SUBMITTED)
        ->and($submission->openRevisionRequest)->toBeNull()
        ->and($submission->revisionRequests()->first()->response)->not->toBeNull();
    Notification::assertSentTo($staff, SubmissionResubmittedNotification::class);
});

test('finance can save detail validate and forward to approval queue', function () {
    Notification::fake();
    [, $staff, $submission] = p3ReviewedSubmission();
    $approver = User::factory()->create();
    $approver->assignRole('finance_approver');

    $this->actingAs($staff)->put(route('finance.submissions.finance-detail.update', $submission), p3FinanceDetailPayload())->assertRedirect();
    $this->actingAs($staff)->post(route('finance.submissions.validate', $submission))->assertRedirect();

    $submission->refresh();
    expect($submission->status)->toBe(SubmissionStatus::FINANCE_VALIDATED)
        ->and($submission->finance_validated_by)->toBe($staff->id)
        ->and($submission->financeDetail)->not->toBeNull();

    $this->actingAs($staff)->post(route('finance.submissions.forward-approval', $submission))->assertRedirect();

    $submission->refresh();
    expect($submission->status)->toBe(SubmissionStatus::APPROVAL_REVIEW)
        ->and($submission->forwarded_to_approval_by)->toBe($staff->id);
    Notification::assertSentTo($approver, SubmissionForwardedToApprovalNotification::class);
    $this->actingAs($approver)->get(route('approval.submissions.index'))->assertOk();
    $this->actingAs($approver)->get(route('approval.submissions.show', $submission))->assertOk();
});

test('pic can cancel a revision requested submission', function () {
    [$pic, $staff, $submission] = p3ReviewedSubmission();

    $this->actingAs($staff)->post(route('finance.submissions.request-revision', $submission), [
        'subject' => 'Perbaiki data',
        'message' => 'Mohon revisi atau batalkan jika tidak dilanjutkan.',
        'fields' => ['other'],
    ])->assertRedirect();

    $this->actingAs($pic)->post(route('submissions.cancel', $submission), ['reason' => 'Tidak dilanjutkan.'])->assertRedirect();

    $submission->refresh();
    expect($submission->status)->toBe(SubmissionStatus::CANCELLED)
        ->and($submission->openRevisionRequest)->toBeNull()
        ->and($submission->revisionRequests()->first()->status->value)->toBe('cancelled');
});
