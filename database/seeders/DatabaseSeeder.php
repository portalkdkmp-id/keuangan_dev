<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        $this->call(SubmissionCategorySeeder::class);
        $this->call(SubmissionRequestMasterSeeder::class);

        $superadmin = User::firstOrCreate(
            ['email' => 'admin@perdanaerda.com'],
            [
                'name' => 'Erda',
                'phone' => '080808',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $pic = User::firstOrCreate(
            ['email' => 'pic1@mail.com'],
            [
                'name' => 'PIC 1',
                'phone' => '5001',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $staffkeuangan = User::firstOrCreate(
            ['email' => 'staffkeuangan@mail.com'],
            [
                'name' => 'Staff Keuangan',
                'phone' => '4001',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $superadmin->assignRole('super_admin');
        $pic->assignRole('pic_kdkmp');
        $staffkeuangan->assignRole('finance_staff');
    }
}
