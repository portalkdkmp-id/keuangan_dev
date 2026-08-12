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
            ['email' => 'superadmin@mail.com'],
            [
                'name' => 'SUPER ADMIN',
                'phone' => '080808',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $pic1 = User::firstOrCreate(
            ['email' => 'pic1@mail.com'],
            [
                'name' => 'PIC 1',
                'phone' => '5001',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $pic2 = User::firstOrCreate(
            ['email' => 'pic2@mail.com'],
            [
                'name' => 'PIC 2',
                'phone' => '5002',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $staffkeuangan = User::firstOrCreate(
            ['email' => 'staff@mail.com'],
            [
                'name' => 'Staff Keuangan',
                'phone' => '4001',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        
        $approval = User::firstOrCreate(
            ['email' => 'approval@mail.com'],
            [
                'name' => 'Approver',
                'phone' => '3001',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $direktur = User::firstOrCreate(
            ['email' => 'direktur@mail.com'],
            [
                'name' => 'Direktur',
                'phone' => '2001',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $superadmin->assignRole('super_admin');
        $pic1->assignRole('pic_kdkmp');
        $pic2->assignRole('pic_kdkmp');
        $approval->assignRole('finance_approver');
        $staffkeuangan->assignRole('finance_staff');
        $direktur->assignRole('finance_director');
    }
}
