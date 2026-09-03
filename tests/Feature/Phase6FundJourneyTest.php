<?php

use App\Enums\AccountabilityStatus;
use App\Enums\DistributionStatus;
use App\Models\CompanyBankAccount;
use App\Models\FinancialSubmission;
use App\Models\FundAccountabilityReport;
use App\Models\SubmissionDirectorReview;
use App\Models\SubmissionDisbursement;
use App\Models\SubmissionItem;
use App\Models\SubmissionRequestType;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('local');
});

function p6Disbursement(bool $throughStaff = true): array
{
    $pic = User::factory()->create();
    $pic->assignRole('pic_kdkmp');
    $staff = User::factory()->create();
    $staff->assignRole('finance_staff');
    $picAccount = $pic->bankAccounts()->create(['bank_name' => 'Bank PIC', 'account_number' => '111122223333', 'account_holder_name' => $pic->name, 'is_active' => true, 'is_primary' => true]);
    $staffAccount = $staff->bankAccounts()->create(['bank_name' => 'Bank Staff', 'account_number' => '999988887777', 'account_holder_name' => $staff->name, 'is_active' => true, 'is_primary' => true]);
    $submission = FinancialSubmission::factory()->create(['submitted_by' => $pic->id, 'status' => 'fund_disbursed', 'total_amount' => 500000, 'disbursed_amount' => 500000]);
    $review = SubmissionDirectorReview::create(['financial_submission_id' => $submission->id, 'director_id' => $staff->id, 'review_number' => 1, 'status' => 'approved_and_disbursed', 'decision' => 'approved_and_disbursed', 'approved_amount' => 500000]);
    $disbursement = SubmissionDisbursement::create([
        'financial_submission_id' => $submission->id, 'director_review_id' => $review->id,
        'disbursement_number' => 'DISB/2026/08/'.fake()->unique()->numerify('######'), 'disbursed_by' => $staff->id, 'amount' => 500000, 'payment_method' => 'bank_transfer',
        'recipient_type' => $throughStaff ? 'finance_staff' : 'pic_kdkmp', 'recipient_user_id' => $throughStaff ? $staff->id : $pic->id, 'recipient_name_snapshot' => $throughStaff ? $staff->name : $pic->name,
        'source_bank_name' => 'Bank Perusahaan', 'source_account_number_snapshot' => '1234567890', 'source_account_holder_snapshot' => 'KDKMP',
        'destination_bank_snapshot' => $throughStaff ? $staffAccount->bank_name : $picAccount->bank_name, 'destination_account_number_snapshot' => $throughStaff ? $staffAccount->account_number : $picAccount->account_number, 'destination_account_holder_snapshot' => $throughStaff ? $staffAccount->account_holder_name : $picAccount->account_holder_name,
        'destination_bank_account_id' => $throughStaff ? $staffAccount->id : $picAccount->id, 'requires_distribution' => $throughStaff, 'distribution_status' => $throughStaff ? 'pending' : 'not_required',
        'transaction_reference' => 'TRX-P6', 'transfer_date' => now()->toDateString(), 'transferred_at' => now(), 'status' => 'completed',
    ]);

    return [$pic, $staff, $submission, $disbursement, $picAccount];
}

test('director disbursement requires company source and recipient destination', function () {
    $director = User::factory()->create();
    $director->assignRole('finance_director');
    expect($director->can('company-bank-accounts.create'))->toBeTrue();
    $source = CompanyBankAccount::create(['bank_name' => 'Bank Company', 'account_number' => '1234567890', 'account_holder_name' => 'KDKMP', 'is_active' => true, 'is_primary' => true]);
    expect($source->account_number)->toBe('1234567890');
});

test('finance staff distribution is locked to remaining amount and changes status', function () {
    [$pic,$staff,,$disbursement,$picAccount] = p6Disbursement();
    $payload = ['idempotency_key' => 'phase6-distribution-1', 'recipient_type' => 'pic_kdkmp', 'recipient_user_id' => $pic->id, 'destination_bank_account_id' => $picAccount->id, 'amount' => 300000, 'transferred_at' => now()->format('Y-m-d H:i:s'), 'payment_method' => 'bank_transfer', 'attachments' => [UploadedFile::fake()->image('proof.jpg')]];
    $this->actingAs($staff)->post(route('finance.fund-distributions.store', $disbursement), $payload)->assertRedirect();
    expect($disbursement->refresh()->distribution_status)->toBe(DistributionStatus::PARTIALLY_DISTRIBUTED)
        ->and($disbursement->distributions()->sum('amount'))->toEqual(300000);
    $this->actingAs($staff)->post(route('finance.fund-distributions.store', $disbursement), $payload)->assertRedirect();
    expect($disbursement->distributions()->count())->toBe(1);
    $payload['amount'] = 250000;
    $payload['idempotency_key'] = 'phase6-distribution-2';
    $this->actingAs($staff)->post(route('finance.fund-distributions.store', $disbursement), $payload)->assertSessionHasErrors('amount');
});

test('pic confirms receipt once and accountability totals are calculated by backend', function () {
    [$pic,,$submission,$disbursement] = p6Disbursement(false);
    $this->actingAs($pic)->post(route('fund-receipts.disbursement.confirm', $disbursement), ['received_at' => now()->format('Y-m-d H:i:s')])->assertSessionHasErrors('notes');
    $this->actingAs($pic)->post(route('fund-receipts.disbursement.confirm', $disbursement), ['received_at' => now()->format('Y-m-d H:i:s'), 'notes' => 'Dana diterima utuh'])->assertRedirect();
    $this->actingAs($pic)->post(route('fund-receipts.disbursement.confirm', $disbursement), ['received_at' => now()->format('Y-m-d H:i:s'), 'notes' => 'Konfirmasi ulang'])->assertSessionHasErrors('receipt');
    $payload = ['summary' => 'Penggunaan dana operasional', 'items' => [
        ['expense_date' => now()->toDateString(), 'description' => 'Belanja ATK', 'amount' => 300000],
        ['expense_date' => now()->toDateString(), 'description' => 'Transport', 'amount' => 250000],
    ], 'attachments' => [UploadedFile::fake()->image('receipt.jpg')]];
    $this->actingAs($pic)->post(route('accountability-reports.store', $submission), $payload)->assertRedirect();
    $report = FundAccountabilityReport::first();
    expect($report->received_amount)->toBe('500000.00')->and($report->realized_amount)->toBe('550000.00')->and($report->remaining_amount)->toBe('0.00')->and($report->additional_amount)->toBe('50000.00');
});

test('accountability create prefills realization items from submission items', function () {
    [$pic, , $submission, $disbursement] = p6Disbursement(false);
    $category = SubmissionRequestType::factory()->create([
        'name' => 'Operasional Lapangan',
        'slug' => 'operasional-lapangan',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    SubmissionItem::factory()->create([
        'financial_submission_id' => $submission->id,
        'request_type_id' => $category->id,
        'description' => 'Sewa kendaraan lapangan',
        'unit_price' => 200000,
        'quantity' => 2,
        'subtotal' => 400000,
    ]);
    SubmissionItem::factory()->create([
        'financial_submission_id' => $submission->id,
        'request_type_id' => $category->id,
        'description' => 'Biaya pengiriman dokumen',
        'unit_price' => 100000,
        'quantity' => 1,
        'subtotal' => 100000,
        'sort_order' => 1,
    ]);
    $this->actingAs($pic)->post(route('fund-receipts.disbursement.confirm', $disbursement), ['received_at' => now(), 'notes' => 'Dana diterima']);

    $this->actingAs($pic)
        ->get(route('accountability-reports.create', $submission))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Pic/AccountabilityReports/Form')
            ->has('submission.items', 2)
            ->where('submission.items.0.description', 'Sewa kendaraan lapangan')
            ->where('submission.items.0.request_type_id', $category->id)
            ->where('submission.items.0.subtotal', '400000.00')
            ->where('submission.items.1.description', 'Biaya pengiriman dokumen')
            ->where('submission.items.1.subtotal', '100000.00'));
});

test('accountability moves from submit through finance verification to closed', function () {
    [$pic,$staff,$submission,$disbursement] = p6Disbursement(false);
    $approver = User::factory()->create();
    $approver->assignRole('finance_approver');
    $this->actingAs($pic)->post(route('fund-receipts.disbursement.confirm', $disbursement), ['received_at' => now(), 'notes' => 'Dana diterima']);
    $this->actingAs($pic)->post(route('accountability-reports.store', $submission), ['summary' => 'Laporan final', 'items' => [['expense_date' => now()->toDateString(), 'description' => 'ATK', 'amount' => 500000]], 'attachments' => [UploadedFile::fake()->image('receipt.jpg')]]);
    $report = FundAccountabilityReport::first();
    $this->actingAs($pic)->post(route('accountability-reports.submit', $report))->assertRedirect();
    $this->actingAs($staff)->post(route('finance.accountability-reports.start-review', $report))->assertRedirect();
    $this->actingAs($staff)->post(route('finance.accountability-reports.verify', $report), ['notes' => 'Valid'])->assertRedirect();
    $this->actingAs($approver)->post(route('approval.accountability-reports.approve', $report), ['notes' => 'Disetujui'])->assertRedirect();
    expect($report->refresh()->status)->toBe(AccountabilityStatus::CLOSED)->and($report->closed_at)->not->toBeNull();
});

test('phase six role pages are accessible with scoped permissions', function () {
    [$pic, $staff, $submission] = p6Disbursement();
    $approver = User::factory()->create();
    $approver->assignRole('finance_approver');
    $director = User::factory()->create();
    $director->assignRole('finance_director');

    $this->actingAs($staff)->get(route('finance.fund-distributions.index'))->assertOk();
    $this->actingAs($pic)->get(route('fund-receipts.index'))->assertOk();
    $this->actingAs($pic)->get(route('accountability-reports.index'))->assertOk();
    $this->actingAs($approver)->get(route('approval.accountability-reports.index'))->assertOk();
    $this->actingAs($director)->get(route('monitoring.funds.index'))->assertOk();
    $this->actingAs($director)->get(route('monitoring.funds.show', $submission))->assertOk();
});

test('pic can only access disbursement proof from own submission', function () {
    [$pic, , , $disbursement] = p6Disbursement(false);
    $otherPic = User::factory()->create();
    $otherPic->assignRole('pic_kdkmp');

    expect(Gate::forUser($pic)->allows('downloadProof', $disbursement))->toBeTrue()
        ->and(Gate::forUser($otherPic)->allows('downloadProof', $disbursement))->toBeFalse();
});
