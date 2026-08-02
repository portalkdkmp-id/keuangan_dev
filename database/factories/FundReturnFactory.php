<?php

namespace Database\Factories;

use App\Models\CompanyBankAccount;
use App\Models\FinancialSubmission;
use App\Models\FundAccountabilityReport;
use App\Models\FundReturn;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FundReturnFactory extends Factory
{
    protected $model = FundReturn::class;

    public function definition(): array
    {
        $user = User::factory()->create();
        $submission = FinancialSubmission::factory()->create(['submitted_by' => $user->id]);
        $report = FundAccountabilityReport::create(['financial_submission_id' => $submission->id, 'submitted_by' => $user->id, 'report_number' => 'ACC/'.fake()->unique()->numerify('######'), 'status' => 'return_pending', 'received_amount' => 1000000, 'realized_amount' => 750000, 'remaining_amount' => 250000, 'additional_amount' => 0, 'summary' => 'Test']);
        $company = CompanyBankAccount::create(['bank_name' => 'Bank Company', 'account_number' => fake()->numerify('########'), 'account_holder_name' => 'Company', 'is_active' => true]);

        return ['financial_submission_id' => $submission->id, 'fund_accountability_report_id' => $report->id, 'return_number' => 'RET/'.now()->format('Y/m').'/'.fake()->unique()->numerify('######'), 'returned_by' => $user->id, 'destination_company_bank_account_id' => $company->id, 'destination_bank_name_snapshot' => $company->bank_name, 'destination_account_number_snapshot' => $company->account_number, 'destination_account_holder_snapshot' => $company->account_holder_name, 'expected_amount' => 250000, 'returned_amount' => 250000, 'transfer_date' => now()->toDateString(), 'transferred_at' => now(), 'payment_method' => 'bank_transfer', 'status' => 'draft'];
    }
}
