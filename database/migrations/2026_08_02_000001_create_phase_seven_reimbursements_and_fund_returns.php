<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submission_request_categories', fn (Blueprint $table) => $table->string('code')->nullable()->unique()->after('slug'));

        Schema::create('reimbursement_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('financial_submission_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignUuid('claimant_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('claimant_bank_account_id')->nullable()->constrained('user_bank_accounts')->nullOnDelete();
            $table->string('claimant_bank_name_snapshot');
            $table->string('claimant_account_number_snapshot');
            $table->string('claimant_account_holder_snapshot');
            $table->decimal('expense_total', 18, 2)->default(0);
            $table->decimal('claimed_amount', 18, 2)->default(0);
            $table->decimal('finance_validated_amount', 18, 2)->nullable();
            $table->decimal('approval_approved_amount', 18, 2)->nullable();
            $table->decimal('director_approved_amount', 18, 2)->nullable();
            $table->decimal('paid_amount', 18, 2)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->date('expense_date_from')->nullable();
            $table->date('expense_date_to')->nullable();
            $table->text('summary')->nullable();
            $table->text('finance_notes')->nullable();
            $table->text('approval_notes')->nullable();
            $table->text('director_notes')->nullable();
            $table->string('source_type')->default('manual');
            $table->foreignUuid('source_accountability_report_id')->nullable()->unique()->constrained('fund_accountability_reports')->restrictOnDelete();
            $table->decimal('source_additional_amount', 18, 2)->nullable();
            $table->timestamps();
        });
        Schema::create('reimbursement_expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('reimbursement_detail_id')->constrained()->cascadeOnDelete();
            $table->date('expense_date');
            $table->foreignUuid('expense_type_id')->nullable()->constrained('submission_request_types')->nullOnDelete();
            $table->string('expense_type_name_snapshot');
            $table->string('vendor_name');
            $table->text('description');
            $table->decimal('actual_amount', 18, 2);
            $table->string('payment_method');
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('reimbursement_expense_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('reimbursement_expense_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('attachment_type');
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('disk');
            $table->string('path');
            $table->string('mime_type');
            $table->string('extension')->nullable();
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });
        Schema::create('fund_returns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('financial_submission_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('fund_accountability_report_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignUuid('fund_receipt_confirmation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('return_number')->unique();
            $table->foreignUuid('returned_by')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('source_user_bank_account_id')->nullable()->constrained('user_bank_accounts')->nullOnDelete();
            $table->string('source_bank_name_snapshot')->nullable();
            $table->string('source_account_number_snapshot')->nullable();
            $table->string('source_account_holder_snapshot')->nullable();
            $table->foreignUuid('destination_company_bank_account_id')->constrained('company_bank_accounts')->restrictOnDelete();
            $table->string('destination_bank_name_snapshot');
            $table->string('destination_account_number_snapshot');
            $table->string('destination_account_holder_snapshot');
            $table->decimal('expected_amount', 18, 2);
            $table->decimal('returned_amount', 18, 2);
            $table->date('transfer_date');
            $table->timestamp('transferred_at');
            $table->string('payment_method');
            $table->string('transaction_reference')->nullable();
            $table->text('notes')->nullable();
            $table->text('verification_notes')->nullable();
            $table->text('approval_notes')->nullable();
            $table->text('revision_notes')->nullable();
            $table->string('status')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('fund_return_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('fund_return_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('attachment_type');
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('disk');
            $table->string('path');
            $table->string('mime_type');
            $table->string('extension')->nullable();
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });
        DB::table('submission_request_categories')->where('slug', 'pengajuan-reimbursement')->update(['code' => 'reimbursement']);
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE reimbursement_details ADD CONSTRAINT reimbursement_amounts_check CHECK (expense_total >= 0 AND claimed_amount >= 0 AND claimed_amount <= expense_total)');
            DB::statement('ALTER TABLE reimbursement_expenses ADD CONSTRAINT reimbursement_expense_amount_check CHECK (actual_amount > 0)');
            DB::statement('ALTER TABLE fund_returns ADD CONSTRAINT fund_return_amount_check CHECK (expected_amount > 0 AND returned_amount = expected_amount)');
            DB::statement('ALTER TABLE fund_accountability_reports ADD CONSTRAINT accountability_single_difference_check CHECK (NOT (remaining_amount > 0 AND additional_amount > 0))');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE fund_accountability_reports DROP CONSTRAINT IF EXISTS accountability_single_difference_check');
        }
        Schema::dropIfExists('fund_return_attachments');
        Schema::dropIfExists('fund_returns');
        Schema::dropIfExists('reimbursement_expense_attachments');
        Schema::dropIfExists('reimbursement_expenses');
        Schema::dropIfExists('reimbursement_details');
        Schema::table('submission_request_categories', fn (Blueprint $table) => $table->dropColumn('code'));
    }
};
