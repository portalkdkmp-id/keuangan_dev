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

    $this->actingAs($pic)->get(route('submissions.create'))->assertOk();

    $this->actingAs($pic)->post(route('submissions.store'), submissionPayload($other))->assertSessionHasErrors('cooperative_id');

    $this->actingAs($pic)->post(route('submissions.store'), submissionPayload($cooperative))->assertRedirect();

    $submission = FinancialSubmission::first();
    $this->actingAs($pic)->get(route('submissions.review', $submission))->assertOk();

    expect($submission->submitted_by)->toBe($pic->id)
        ->and($submission->status)->toBe(SubmissionStatus::DRAFT)
        ->and((float) $submission->total_amount)->toBe(300000.0)
        ->and((float) $submission->items()->first()->subtotal)->toBe(300000.0);
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
