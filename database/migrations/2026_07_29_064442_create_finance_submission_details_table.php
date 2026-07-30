<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('finance_submission_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('financial_submission_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('budget_account_code', 100)->nullable();
            $table->string('budget_account_name')->nullable();
            $table->string('cost_center_code', 100)->nullable();
            $table->string('cost_center_name')->nullable();
            $table->string('expense_group')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('beneficiary_name')->nullable();
            $table->string('beneficiary_bank')->nullable();
            $table->string('beneficiary_account_number', 100)->nullable();
            $table->string('beneficiary_account_holder')->nullable();
            $table->boolean('tax_applicable')->default(false);
            $table->text('tax_notes')->nullable();
            $table->text('finance_notes')->nullable();
            $table->decimal('validated_total_amount', 18, 2)->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_submission_details');
    }
};
