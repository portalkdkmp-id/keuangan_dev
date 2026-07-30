<?php

namespace Database\Seeders;

use App\Models\SubmissionRequestCategory;
use App\Models\SubmissionRequestType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SubmissionRequestMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['Pengajuan Dana KDKMP', 'Operasional tim Sales', 'Pengajuan Reimbursement'] as $index => $name) {
            SubmissionRequestCategory::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true, 'sort_order' => $index]
            );
        }

        foreach (['Sewa Kendaraan', 'Biaya Ongkir', 'ATK dan Fotocopy', 'Sarana Prasarana'] as $index => $name) {
            SubmissionRequestType::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true, 'sort_order' => $index]
            );
        }
    }
}
