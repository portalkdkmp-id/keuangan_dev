<?php

namespace Database\Seeders;

use App\Models\SubmissionCategory;
use Illuminate\Database\Seeder;

class SubmissionCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['operational', 'Operasional'],
            ['facility', 'Sarana dan Prasarana'],
            ['transportation', 'Transportasi'],
            ['accommodation', 'Penginapan'],
            ['office_supplies', 'ATK dan Fotokopi'],
            ['utility', 'Utilitas'],
            ['meeting', 'Rapat dan Koordinasi'],
            ['other', 'Lainnya'],
        ];

        foreach ($categories as $index => [$code, $name]) {
            SubmissionCategory::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'is_active' => true, 'sort_order' => $index + 1]
            );
        }
    }
}
