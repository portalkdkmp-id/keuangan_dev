<?php

use App\Models\City;
use App\Models\FinancialSubmission;
use App\Models\FundAccountabilityReport;
use App\Models\SubmissionItem;
use App\Models\SubmissionStatusHistory;
use App\Models\User;
use App\Services\Export\FinancialReportExcelExportService;
use Database\Seeders\RolePermissionSeeder;
use ZipArchive;

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

test('report search finds a submission by item name', function () {
    $staff = User::factory()->create();
    $staff->assignRole('finance_staff');
    $submission = FinancialSubmission::factory()->create(['title' => 'Pengajuan umum']);
    SubmissionItem::factory()->create([
        'financial_submission_id' => $submission->id,
        'description' => 'Printer gudang khusus',
    ]);
    FinancialSubmission::factory()->create(['title' => 'Pengajuan lainnya']);

    $this->actingAs($staff)
        ->get(route('submission-reports.index', ['search' => 'Printer gudang']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('submissions.data', 1)
            ->where('submissions.data.0.id', $submission->id));
});

test('complete financial report contains three template sheets and scopes pic data', function () {
    $city = City::factory()->create(['name' => 'Kabupaten Area Export']);
    $pic = User::factory()->create(['city_id' => $city->id]);
    $pic->assignRole('pic_kdkmp');
    $otherPic = User::factory()->create();
    $otherPic->assignRole('pic_kdkmp');

    $own = FinancialSubmission::factory()->create([
        'submitted_by' => $pic->id,
        'submitter_city_id' => $city->id,
        'is_urgent' => true,
        'disbursed_at' => now()->subDays(10),
        'disbursed_amount' => 500000,
    ]);
    $other = FinancialSubmission::factory()->create([
        'submitted_by' => $otherPic->id,
        'disbursed_at' => now()->subDays(20),
        'disbursed_amount' => 700000,
    ]);
    FundAccountabilityReport::create([
        'financial_submission_id' => $own->id,
        'submitted_by' => $pic->id,
        'report_number' => 'LPJ/TEST/OWN',
        'status' => 'submitted',
        'received_amount' => 500000,
        'realized_amount' => 450000,
        'remaining_amount' => 50000,
        'additional_amount' => 0,
        'summary' => 'LPJ milik PIC',
        'submitted_at' => now()->subDay(),
    ]);
    FundAccountabilityReport::create([
        'financial_submission_id' => $other->id,
        'submitted_by' => $otherPic->id,
        'report_number' => 'LPJ/TEST/OTHER',
        'status' => 'submitted',
        'received_amount' => 700000,
        'realized_amount' => 700000,
        'remaining_amount' => 0,
        'additional_amount' => 0,
        'summary' => 'LPJ PIC lain',
    ]);

    $path = app(FinancialReportExcelExportService::class)->generate($pic, [], 'complete');
    $zip = new ZipArchive;
    expect($zip->open($path))->toBeTrue();
    $workbook = $zip->getFromName('xl/workbook.xml');
    $recap = $zip->getFromName('xl/worksheets/sheet1.xml');
    $lpj = $zip->getFromName('xl/worksheets/sheet2.xml');

    expect($workbook)
        ->toContain('1. Rekap Pengajuan Dana')
        ->toContain('2. LPJ')
        ->toContain('3. Aging-Outstanding');
    expect($recap)->toContain('Wilayah Assignment PIC')->toContain('Kabupaten Area Export');
    expect($lpj)->toContain('LPJ/TEST/OWN')->not->toContain('LPJ/TEST/OTHER');
    $zip->close();
    @unlink($path);
});
