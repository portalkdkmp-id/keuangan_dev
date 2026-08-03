<?php

use App\Enums\AccountabilityStatus;
use App\Enums\FundReturnStatus;
use App\Enums\SubmissionType;
use App\Models\CompanyBankAccount;
use App\Models\Cooperative;
use App\Models\FinancialSubmission;
use App\Models\FundAccountabilityReport;
use App\Models\SubmissionRequestType;
use App\Models\User;
use App\Services\FundReturn\FundReturnService;
use App\Services\Reimbursement\ReimbursementService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SubmissionRequestMasterSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed([RolePermissionSeeder::class, SubmissionRequestMasterSeeder::class]);
    Storage::fake('local');
});

function p7Actor(): array
{
    $user = User::factory()->create();
    $user->assignRole('pic_kdkmp');
    $cooperative = Cooperative::factory()->create();
    $user->assignedCooperatives()->attach($cooperative->id, ['assigned_by' => $user->id, 'assigned_at' => now(), 'is_primary' => true]);
    $account = $user->bankAccounts()->create(['bank_name' => 'Bank User', 'account_number' => '1234567890', 'account_holder_name' => $user->name, 'is_active' => true, 'is_primary' => true]);

    return [$user, $cooperative, $account];
}

function p7Reimbursement(bool $completeProofs = true): array
{
    [$user, $cooperative, $account] = p7Actor();
    $type = SubmissionRequestType::firstOrFail();
    $expense = ['expense_date' => now()->toDateString(), 'expense_type_id' => $type->id, 'vendor_name' => 'Vendor A', 'description' => 'Pembelian kebutuhan operasional', 'actual_amount' => '500000', 'payment_method' => 'bank_transfer'];
    $purchase = [0 => [UploadedFile::fake()->image('nota.jpg')]];
    $payment = $completeProofs ? [0 => [UploadedFile::fake()->image('transfer.jpg')]] : [];
    $submission = app(ReimbursementService::class)->createDraft($user, ['title' => 'Reimbursement ATK', 'cooperative_id' => $cooperative->id, 'claimant_bank_account_id' => $account->id, 'summary' => 'Penggantian pembelian ATK', 'expenses' => [$expense]], $purchase, $payment);

    return [$user, $submission];
}

test('reimbursement is identified by stable domain and total is calculated by backend', function () {
    [$user, $submission] = p7Reimbursement();
    $detail = $submission->reimbursementDetail;

    expect($submission->type)->toBe(SubmissionType::REIMBURSEMENT)
        ->and($submission->isReimbursement())->toBeTrue()
        ->and($submission->total_amount)->toBe('500000.00')
        ->and($detail->claimed_amount)->toBe('500000.00')
        ->and($detail->claimant_account_number_snapshot)->toBe('1234567890')
        ->and($detail->claimant_user_id)->toBe($user->id);
});

test('reimbursement cannot be submitted without distinct payment proof', function () {
    [$user, $submission] = p7Reimbursement(false);

    expect(fn () => app(ReimbursementService::class)->submit($user, $submission))->toThrow(ValidationException::class);
    expect($submission->refresh()->status->value)->toBe('draft');
});

test('complete reimbursement can be submitted once', function () {
    [$user, $submission] = p7Reimbursement();
    app(ReimbursementService::class)->submit($user, $submission);

    expect($submission->refresh()->status->value)->toBe('submitted');
    expect(fn () => app(ReimbursementService::class)->submit($user, $submission))->toThrow(ValidationException::class);
});

test('reimbursement appears in the unified submission list', function () {
    [$user, $submission] = p7Reimbursement();

    $this->actingAs($user)->get(route('submissions.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Submissions/Index')
            ->where('submissions.data.0.id', $submission->id)
            ->where('submissions.data.0.type', 'reimbursement'));
});

test('finance staff can open both submission creation flows', function () {
    $staff = User::factory()->create();
    $staff->assignRole('finance_staff');
    Cooperative::factory()->create(['is_active' => true]);

    $this->actingAs($staff)->get(route('submissions.create'))->assertOk()->assertInertia(fn ($page) => $page->has('cooperatives', 1));
    $this->actingAs($staff)->get(route('reimbursements.create'))->assertOk()->assertInertia(fn ($page) => $page->has('cooperatives', 1));
});

test('fund return expected amount is immutable and approval closes accountability', function () {
    [$pic, $cooperative] = p7Actor();
    $staff = User::factory()->create();
    $staff->assignRole('finance_staff');
    $approver = User::factory()->create();
    $approver->assignRole('finance_approver');
    $submission = FinancialSubmission::factory()->create(['submitted_by' => $pic->id, 'cooperative_id' => $cooperative->id]);
    $report = FundAccountabilityReport::create(['financial_submission_id' => $submission->id, 'submitted_by' => $pic->id, 'report_number' => 'ACC/2026/08/999999', 'status' => AccountabilityStatus::RETURN_PENDING, 'received_amount' => 1000000, 'realized_amount' => 750000, 'remaining_amount' => 250000, 'additional_amount' => 0, 'summary' => 'Laporan dengan sisa dana']);
    $company = CompanyBankAccount::create(['bank_name' => 'Bank Perusahaan', 'account_number' => '99887766', 'account_holder_name' => 'KDKMP', 'is_active' => true, 'is_primary' => true]);
    $service = app(FundReturnService::class);
    $return = $service->createDraft($pic, $report, ['source_user_bank_account_id' => $pic->bankAccounts()->first()->id, 'destination_company_bank_account_id' => $company->id, 'transfer_date' => now()->toDateString(), 'transferred_at' => now(), 'payment_method' => 'bank_transfer'], UploadedFile::fake()->image('return.jpg'));

    expect($return->expected_amount)->toBe('250000.00')->and($return->returned_amount)->toBe('250000.00');
    $service->submit($pic, $return);
    $service->startReview($staff, $return);
    $service->verify($staff, $return, 'Valid');
    $service->approve($approver, $return, 'Diterima');
    expect($return->refresh()->status)->toBe(FundReturnStatus::CLOSED)->and($report->refresh()->status)->toBe(AccountabilityStatus::CLOSED)->and($report->closed_at)->not->toBeNull();
});

test('another user cannot view private fund return', function () {
    [$pic, $cooperative] = p7Actor();
    $other = User::factory()->create();
    $other->assignRole('pic_kdkmp');
    $submission = FinancialSubmission::factory()->create(['submitted_by' => $pic->id, 'cooperative_id' => $cooperative->id]);
    $report = FundAccountabilityReport::create(['financial_submission_id' => $submission->id, 'submitted_by' => $pic->id, 'report_number' => 'ACC/2026/08/888888', 'status' => AccountabilityStatus::RETURN_PENDING, 'received_amount' => 100, 'realized_amount' => 50, 'remaining_amount' => 50, 'additional_amount' => 0, 'summary' => 'Sisa']);
    $company = CompanyBankAccount::create(['bank_name' => 'Bank Company', 'account_number' => '9999', 'account_holder_name' => 'Company', 'is_active' => true]);
    $return = app(FundReturnService::class)->createDraft($pic, $report, ['source_user_bank_account_id' => $pic->bankAccounts()->first()->id, 'destination_company_bank_account_id' => $company->id, 'transfer_date' => now()->toDateString(), 'transferred_at' => now(), 'payment_method' => 'bank_transfer'], UploadedFile::fake()->image('proof.jpg'));

    $this->actingAs($other)->get(route('fund-returns.show', $return))->assertForbidden();
});
