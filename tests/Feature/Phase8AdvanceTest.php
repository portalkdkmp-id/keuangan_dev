<?php

use App\Enums\AdvanceStatus;
use App\Enums\SubmissionType;
use App\Models\Cooperative;
use App\Models\FinancialSubmission;
use App\Models\SubmissionRequestType;
use App\Models\User;
use App\Services\Accountability\FundAccountabilityService;
use App\Services\Advance\AdvanceService;
use App\Services\AdvanceSettlement\AdvanceSettlementService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SubmissionRequestMasterSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed([RolePermissionSeeder::class, SubmissionRequestMasterSeeder::class]);
    Storage::fake('local');
});

function p8Advance(float $amount = 1000000): array
{
    $staff = User::factory()->create();
    $staff->assignRole('finance_staff');
    $cooperative = Cooperative::factory()->create();
    $account = $staff->bankAccounts()->create(['bank_name' => 'Bank Staff', 'account_number' => '1234567890', 'account_holder_name' => $staff->name, 'is_active' => true, 'is_primary' => true]);
    $submission = app(AdvanceService::class)->createDraft($staff, [
        'title' => 'Panjar pembelian operasional',
        'cooperative_id' => $cooperative->id,
        'purpose' => 'Pembelian kebutuhan dengan harga belum pasti',
        'estimated_amount' => $amount,
        'expected_transaction_date' => now()->addDays(7)->toDateString(),
        'expected_settlement_date' => now()->addDays(7)->toDateString(),
        'recipient_bank_account_id' => $account->id,
        'notes' => null,
    ]);

    return [$staff, $submission, $submission->advanceDetail];
}

function p8Settlement(float $realized): array
{
    [$staff, $submission, $advance] = p8Advance();
    $advance->update(['advance_status' => AdvanceStatus::SETTLEMENT_DUE, 'disbursed_amount' => 1000000, 'disbursed_at' => now()]);
    $type = SubmissionRequestType::firstOrFail();
    $report = app(AdvanceSettlementService::class)->saveDraft($staff, $advance, [
        'summary' => 'Realisasi panjar operasional',
        'usage_date_from' => today()->toDateString(),
        'usage_date_to' => today()->toDateString(),
        'items' => [[
            'expense_date' => today()->toDateString(), 'description' => 'Pembelian aktual', 'category_id' => $type->id,
            'amount' => $realized, 'vendor_name' => 'Vendor A', 'invoice_number' => 'INV-001',
            'payment_method' => 'bank_transfer', 'payment_reference' => 'TRX-001', 'notes' => null,
        ]],
    ], [0 => [UploadedFile::fake()->image('nota.jpg')]], [0 => [UploadedFile::fake()->image('transfer.jpg')]]);

    return [$staff, $submission, $advance->refresh(), $report];
}

test('only finance staff can access advance creation and domain uses stable type', function () {
    $pic = User::factory()->create();
    $pic->assignRole('pic_kdkmp');
    $this->actingAs($pic)->get(route('advances.create'))->assertForbidden();

    [$staff, $submission, $advance] = p8Advance();
    expect($submission->type)->toBe(SubmissionType::ADVANCE)
        ->and($submission->isAdvance())->toBeTrue()
        ->and($advance->responsible_user_id)->toBe($staff->id)
        ->and($advance->recipient_account_number_snapshot)->toBe('1234567890');
});

test('finance staff can save advance draft through multipart form endpoint', function () {
    $staff = User::factory()->create();
    $staff->assignRole('finance_staff');
    $cooperative = Cooperative::factory()->create();
    $account = $staff->bankAccounts()->create(['bank_name' => 'Bank Staff', 'account_number' => '9988776655', 'account_holder_name' => $staff->name, 'is_active' => true, 'is_primary' => true]);

    $response = $this->actingAs($staff)->post(route('advances.store'), [
        'title' => 'Panjar kegiatan lapangan',
        'cooperative_id' => $cooperative->id,
        'purpose' => 'Kebutuhan operasional kegiatan lapangan',
        'estimated_amount' => '750000',
        'expected_transaction_date' => now()->addDays(7)->toDateString(),
        'expected_settlement_date' => now()->addDays(7)->toDateString(),
        'recipient_bank_account_id' => $account->id,
        'notes' => 'Draft dari form Finance Staff',
        'attachments' => [UploadedFile::fake()->create('estimasi.pdf', 100, 'application/pdf')],
    ]);

    $submission = FinancialSubmission::where('submitted_by', $staff->id)->where('type', SubmissionType::ADVANCE)->firstOrFail();
    $response->assertRedirect(route('advances.show', $submission))->assertSessionHasNoErrors();
    expect($submission->total_amount)->toBe('750000.00')
        ->and($submission->is_urgent)->toBeTrue()
        ->and($submission->advanceDetail->recipient_bank_account_id)->toBe($account->id)
        ->and($submission->attachments()->count())->toBe(1);
});

test('finance staff can save internal advance without cooperative', function () {
    $staff = User::factory()->create();
    $staff->assignRole('finance_staff');
    $account = $staff->bankAccounts()->create(['bank_name' => 'Bank Internal', 'account_number' => '112233', 'account_holder_name' => $staff->name, 'is_active' => true]);

    $this->actingAs($staff)->post(route('advances.store'), [
        'title' => 'Panjar internal kantor',
        'cooperative_id' => null,
        'purpose' => 'Kebutuhan kegiatan internal kantor',
        'estimated_amount' => '500000',
        'expected_transaction_date' => now()->addDays(7)->toDateString(),
        'expected_settlement_date' => now()->addDays(7)->toDateString(),
        'recipient_bank_account_id' => $account->id,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $submission = FinancialSubmission::where('submitted_by', $staff->id)->where('type', SubmissionType::ADVANCE)->firstOrFail();
    expect($submission->cooperative_id)->toBeNull()
        ->and($submission->advanceDetail->cooperative_id)->toBeNull();
});

test('advance settlement calculates exact remaining and shortfall amounts', function () {
    [, , , $remaining] = p8Settlement(800000);
    expect($remaining->realized_amount)->toBe('800000.00')
        ->and($remaining->remaining_amount)->toBe('200000.00')
        ->and($remaining->additional_amount)->toBe('0.00');

    [, , , $shortfall] = p8Settlement(1500000);
    expect($shortfall->realized_amount)->toBe('1500000.00')
        ->and($shortfall->remaining_amount)->toBe('0.00')
        ->and($shortfall->additional_amount)->toBe('500000.00');
});

test('responsible staff cannot review own submitted settlement', function () {
    [$staff, , , $report] = p8Settlement(1000000);
    app(AdvanceSettlementService::class)->submit($staff, $report);

    expect(fn () => app(FundAccountabilityService::class)->startReview($staff, $report))->toThrow(ValidationException::class);

    $reviewer = User::factory()->create();
    $reviewer->assignRole('finance_staff');
    app(FundAccountabilityService::class)->startReview($reviewer, $report);
    expect($report->refresh()->finance_reviewed_by)->toBe($reviewer->id);
});

test('settlement cannot be submitted without both proof types per item', function () {
    [$staff, , $advance] = p8Advance();
    $advance->update(['advance_status' => AdvanceStatus::SETTLEMENT_DUE, 'disbursed_amount' => 1000000, 'disbursed_at' => now()]);
    $type = SubmissionRequestType::firstOrFail();
    $report = app(AdvanceSettlementService::class)->saveDraft($staff, $advance, [
        'summary' => 'Bukti belum lengkap',
        'items' => [['expense_date' => today()->toDateString(), 'description' => 'Pembelian', 'category_id' => $type->id, 'amount' => 1000000, 'vendor_name' => 'Vendor', 'payment_method' => 'cash']],
    ], [0 => [UploadedFile::fake()->image('nota.jpg')]], []);

    expect(fn () => app(AdvanceSettlementService::class)->submit($staff, $report))->toThrow(ValidationException::class);
});
