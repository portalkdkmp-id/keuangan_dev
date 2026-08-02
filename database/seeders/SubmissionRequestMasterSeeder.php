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
                ['name' => $name, 'code' => $name === 'Pengajuan Reimbursement' ? 'reimbursement' : Str::slug($name, '_'), 'is_active' => true, 'sort_order' => $index]
            );
        }

        foreach (['Bensin', 'Token KDKMP', 'Sewa Kendaraan', 'Sarana Prasarana', 'Penginapan', 'Transportasi', 'Petty Cash KDKMP', 'ATK dan Fotokopi', 'Biaya Ongkir', 'Lainnya'] as $index => $name) {
            SubmissionRequestType::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true, 'sort_order' => $index]
            );
        }
    }
}
