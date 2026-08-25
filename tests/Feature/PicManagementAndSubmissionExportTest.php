<?php

use App\Models\City;
use App\Models\Cooperative;
use App\Models\FinancialSubmission;
use App\Models\SubmissionAttachment;
use App\Models\User;
use App\Services\Export\SubmissionExcelExportService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('local');
});

test('pic management is available to configured management roles but not pic', function () {
    foreach (['super_admin', 'finance_staff', 'finance_approver'] as $role) {
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user)->get(route('pics.index'))->assertOk();
    }

    $pic = User::factory()->create();
    $pic->assignRole('pic_kdkmp');
    $this->actingAs($pic)->get(route('pics.index'))->assertForbidden();
});

test('specialized pic creation always assigns pic role and requires city', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $city = City::factory()->create();

    $this->actingAs($admin)->post(route('pics.store'), [
        'name' => 'PIC Area Baru', 'email' => 'pic-area@example.test', 'phone' => '08123456789',
        'city_id' => $city->id, 'password' => 'password123', 'is_active' => true,
        'role' => 'super_admin',
    ])->assertRedirect(route('pics.index'));

    $pic = User::where('email', 'pic-area@example.test')->firstOrFail();
    expect($pic->hasRole('pic_kdkmp'))->toBeTrue()
        ->and($pic->hasRole('super_admin'))->toBeFalse()
        ->and($pic->city_id)->toBe($city->id);
});

test('bulk assignment only accepts cooperatives in pic city', function () {
    $staff = User::factory()->create();
    $staff->assignRole('finance_staff');
    $city = City::factory()->create();
    $otherCity = City::factory()->create();
    $pic = User::factory()->create(['city_id' => $city->id]);
    $pic->assignRole('pic_kdkmp');
    $local = Cooperative::factory()->create(['city_id' => $city->id]);
    $outside = Cooperative::factory()->create(['city_id' => $otherCity->id]);

    $this->actingAs($staff)->put(route('pics.assignments.sync', $pic), [
        'cooperative_ids' => [$local->id],
        'visible_cooperative_ids' => [$local->id],
    ])->assertSessionHasNoErrors();
    expect($pic->assignedCooperatives()->whereKey($local->id)->exists())->toBeTrue();

    $this->actingAs($staff)->put(route('pics.assignments.sync', $pic), [
        'cooperative_ids' => [$outside->id],
        'visible_cooperative_ids' => [$outside->id],
    ])->assertSessionHasErrors('cooperative_ids');
    expect($pic->assignedCooperatives()->whereKey($outside->id)->exists())->toBeFalse();
});

test('submission export follows the financial report recap template', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $submission = FinancialSubmission::factory()->create(['submission_number' => 'FR/2026/08/000001', 'title' => 'Pengajuan Export', 'total_amount' => 750000]);
    SubmissionAttachment::factory()->create(['financial_submission_id' => $submission->id, 'uploaded_by' => $submission->submitted_by, 'original_name' => 'rincian-biaya.pdf']);

    $path = app(SubmissionExcelExportService::class)->generate($admin);
    $zip = new ZipArchive;
    expect($zip->open($path))->toBeTrue();
    $submissionXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $workbookXml = $zip->getFromName('xl/workbook.xml');
    $secondSheet = $zip->getFromName('xl/worksheets/sheet2.xml');
    $zip->close();

    expect($submissionXml)->toContain('LAPORAN REKAP PENGAJUAN DANA PERIODIK')
        ->toContain('FR/2026/08/000001')
        ->toContain('Nominal Diajukan (Rp)')
        ->toContain('RINGKASAN TOTAL')
        ->toContain('<c r="M6" s="5" t="n"><v>1</v></c>')
        ->and($workbookXml)->toContain('1. Rekap Pengajuan Dana')
        ->and($secondSheet)->toBeFalse();
    @unlink($path);

    $this->actingAs($admin)->get(route('submissions.export'))->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});
