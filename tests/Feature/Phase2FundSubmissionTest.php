<?php

use App\Enums\SubmissionStatus;
use App\Models\Cooperative;
use App\Models\FinancialSubmission;
use App\Models\SubmissionCategory;
use App\Models\SubmissionRequestCategory;
use App\Models\SubmissionRequestType;
use App\Models\User;
use App\Notifications\NewFinancialSubmissionNotification;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SubmissionCategorySeeder;
use Database\Seeders\SubmissionRequestMasterSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SubmissionCategorySeeder::class);
    $this->seed(SubmissionRequestMasterSeeder::class);
});

function picWithCooperative(): array
{
    $pic = User::factory()->create();
    $pic->assignRole('pic_kdkmp');
    $cooperative = Cooperative::factory()->create();
    $cooperative->pics()->attach($pic->id, ['assigned_by' => null, 'assigned_at' => now(), 'is_primary' => true]);
    $pic->bankAccounts()->create(['bank_name' => 'Bank Test', 'account_number' => '123', 'account_holder_name' => $pic->name, 'is_active' => true, 'is_primary' => true]);

    return [$pic, $cooperative];
}

function submissionPayload(Cooperative $cooperative): array
{
    $category = SubmissionCategory::first();

    return [
        'cooperative_id' => $cooperative->id,
        'submission_request_category_id' => SubmissionRequestCategory::first()->id,
        'submission_request_type_id' => SubmissionRequestType::first()->id,
        'recipient_bank_account_id' => auth()->user()?->bankAccounts()->first()?->id ?? User::role('pic_kdkmp')->first()?->bankAccounts()->first()?->id,
        'amount' => 300000,
        'title' => 'Dana operasional bulan ini',
        'purpose' => 'Kebutuhan operasional KDKMP',
        'needed_date' => now()->addDay()->toDateString(),
        'notes' => null,
        'total_amount' => 999999999,
        'items' => [
            ['category_id' => $category->id, 'description' => 'Pembelian perlengkapan kantor', 'quantity' => 2, 'unit' => 'pcs', 'unit_price' => 150000, 'notes' => null],
        ],
    ];
}

test('pic can create draft only for assigned cooperative and backend calculates total', function () {
    [$pic, $cooperative] = picWithCooperative();
    $other = Cooperative::factory()->create();

    $this->actingAs($pic)->get(route('submissions.create'))->assertOk()->assertInertia(fn ($page) => $page
        ->component('Submissions/Create')
        ->where('canSubmitInternal', false)
        ->has('cooperatives')
        ->has('requestCategories')
        ->has('requestTypes')
        ->has('bankAccounts'));

    $this->actingAs($pic)->post(route('submissions.store'), submissionPayload($other))->assertSessionHasErrors('cooperative_id');

    $this->actingAs($pic)->post(route('submissions.store'), submissionPayload($cooperative))->assertRedirect();

    $submission = FinancialSubmission::first();
    $this->actingAs($pic)->get(route('submissions.review', $submission))->assertOk();

    expect($submission->submitted_by)->toBe($pic->id)
        ->and($submission->status)->toBe(SubmissionStatus::DRAFT)
        ->and((float) $submission->total_amount)->toBe(300000.0)
        ->and((float) $submission->items()->first()->subtotal)->toBe(300000.0);
});

test('one submission number can contain multiple request items', function () {
    [$pic, $cooperative] = picWithCooperative();
    $types = SubmissionRequestType::take(2)->get();
    $payload = submissionPayload($cooperative);
    unset($payload['amount'], $payload['submission_request_type_id']);
    $payload['items'] = [
        ['name' => 'Sewa kendaraan', 'request_type_id' => $types[0]->id, 'amount' => 500000],
        ['name' => 'Pengiriman dokumen', 'request_type_id' => $types[1]->id, 'amount' => 125000],
    ];

    $this->actingAs($pic)->post(route('submissions.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();

    $submission = FinancialSubmission::with('items')->firstOrFail();
    expect($submission->items)->toHaveCount(2)
        ->and((float) $submission->total_amount)->toBe(625000.0)
        ->and($submission->items[0]->description)->toBe('Sewa kendaraan')
        ->and($submission->items[1]->request_type_id)->toBe($types[1]->id)
        ->and($submission->submission_number)->not->toBeEmpty();
});

test('finance staff can create an internal fund submission without cooperative', function () {
    $staff = User::factory()->create();
    $staff->assignRole('finance_staff');
    $account = $staff->bankAccounts()->create(['bank_name' => 'Bank Internal', 'account_number' => '456', 'account_holder_name' => $staff->name, 'is_active' => true]);
    $payload = submissionPayload(Cooperative::factory()->create());
    $payload['cooperative_id'] = null;
    $payload['recipient_bank_account_id'] = $account->id;

    $this->actingAs($staff)->post(route('submissions.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();

    expect(FinancialSubmission::where('submitted_by', $staff->id)->firstOrFail()->cooperative_id)->toBeNull();
});

test('superadmin can create an internal fund submission without cooperative', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $account = $admin->bankAccounts()->create(['bank_name' => 'Bank Admin', 'account_number' => '789', 'account_holder_name' => $admin->name, 'is_active' => true]);
    $payload = submissionPayload(Cooperative::factory()->create());
    $payload['cooperative_id'] = null;
    $payload['recipient_bank_account_id'] = $account->id;

    $this->actingAs($admin)->get(route('submissions.create'))->assertOk();
    $this->actingAs($admin)->post(route('submissions.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();

    expect(FinancialSubmission::where('submitted_by', $admin->id)->firstOrFail()->cooperative_id)->toBeNull();
});

test('pic can submit draft and active finance staff receive database notification', function () {
    Notification::fake();
    [$pic, $cooperative] = picWithCooperative();
    $staff = User::factory()->create();
    $staff->assignRole('finance_staff');
    $inactiveStaff = User::factory()->create(['is_active' => false]);
    $inactiveStaff->assignRole('finance_staff');

    $this->actingAs($pic)->post(route('submissions.store'), submissionPayload($cooperative));
    $submission = FinancialSubmission::first();

    $this->actingAs($pic)->post(route('submissions.submit', $submission))->assertRedirect();

    $submission->refresh();
    expect($submission->status)->toBe(SubmissionStatus::SUBMITTED)
        ->and($submission->submitted_at)->not->toBeNull()
        ->and($submission->current_assignee_role)->toBe('finance_staff')
        ->and($submission->statusHistories()->where('action', 'submitted')->count())->toBe(1);

    Notification::assertSentTo($staff, NewFinancialSubmissionNotification::class);
    Notification::assertNotSentTo($inactiveStaff, NewFinancialSubmissionNotification::class);
});

test('submitted submission cannot be edited by pic and finance can start review once', function () {
    [$pic, $cooperative] = picWithCooperative();
    $staff = User::factory()->create();
    $staff->assignRole('finance_staff');

    $this->actingAs($pic)->post(route('submissions.store'), submissionPayload($cooperative));
    $submission = FinancialSubmission::first();
    $this->actingAs($pic)->post(route('submissions.submit', $submission));

    $this->actingAs($pic)->put(route('submissions.update', $submission), submissionPayload($cooperative))->assertForbidden();
    $this->actingAs($staff)->get(route('finance.submissions.index'))->assertOk();
    $this->actingAs($staff)->post(route('finance.submissions.start-review', $submission))->assertRedirect(route('finance.submissions.show', $submission));

    $submission->refresh();
    expect($submission->status)->toBe(SubmissionStatus::FINANCE_REVIEW)
        ->and($submission->statusHistories()->where('action', 'finance_review_started')->count())->toBe(1);

    $this->actingAs($staff)->post(route('finance.submissions.start-review', $submission))->assertForbidden();
});
