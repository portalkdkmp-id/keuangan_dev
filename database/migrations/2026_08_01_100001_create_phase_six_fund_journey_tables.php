<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_bank_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('bank_name');
            $table->string('account_number');
            $table->string('account_holder_name');
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_primary')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('cooperative_bank_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('cooperative_id')->constrained()->cascadeOnDelete();
            $table->string('bank_name');
            $table->string('account_number');
            $table->string('account_holder_name');
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_primary')->default(false)->index();
            $table->timestamps();
            $table->index(['cooperative_id', 'is_active']);
        });

        Schema::table('submission_disbursements', function (Blueprint $table) {
            $table->string('recipient_type')->nullable()->after('payment_method')->index();
            $table->foreignUuid('recipient_user_id')->nullable()->after('recipient_type')->constrained('users')->nullOnDelete();
            $table->foreignUuid('recipient_cooperative_id')->nullable()->after('recipient_user_id')->constrained('cooperatives')->nullOnDelete();
            $table->string('recipient_name_snapshot')->nullable()->after('recipient_cooperative_id');
            $table->foreignUuid('source_company_bank_account_id')->nullable()->after('recipient_name_snapshot')->constrained('company_bank_accounts')->restrictOnDelete();
            $table->string('source_bank_name')->nullable()->after('source_company_bank_account_id');
            $table->string('source_account_number_snapshot')->nullable()->after('source_bank_name');
            $table->string('source_account_holder_snapshot')->nullable()->after('source_account_number_snapshot');
            $table->foreignUuid('destination_bank_account_id')->nullable()->after('destination_account_holder_snapshot');
            $table->string('destination_reference_type')->nullable()->after('destination_bank_account_id');
            $table->uuid('destination_reference_id')->nullable()->after('destination_reference_type');
            $table->boolean('requires_distribution')->default(false)->after('destination_reference_id')->index();
            $table->string('distribution_status')->nullable()->after('requires_distribution')->index();
            $table->timestamp('received_by_recipient_at')->nullable()->after('distribution_status');
        });

        Schema::create('submission_fund_distributions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('financial_submission_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('submission_disbursement_id')->constrained()->cascadeOnDelete();
            $table->string('distribution_number')->unique();
            $table->string('idempotency_key', 100)->unique();
            $table->foreignUuid('distributed_by')->constrained('users')->restrictOnDelete();
            $table->string('recipient_type');
            $table->foreignUuid('recipient_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('recipient_cooperative_id')->nullable()->constrained('cooperatives')->nullOnDelete();
            $table->string('recipient_name_snapshot');
            $table->string('destination_bank_name_snapshot');
            $table->string('destination_account_number_snapshot');
            $table->string('destination_account_holder_snapshot');
            $table->string('destination_reference_type')->nullable();
            $table->uuid('destination_reference_id')->nullable();
            $table->decimal('amount', 18, 2);
            $table->date('transfer_date');
            $table->timestamp('transferred_at');
            $table->string('transaction_reference')->nullable();
            $table->string('payment_method');
            $table->text('notes')->nullable();
            $table->string('status')->index();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignUuid('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['submission_disbursement_id', 'status']);
            $table->index(['recipient_user_id', 'status']);
        });

        Schema::create('fund_distribution_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('fund_distribution_id')->constrained('submission_fund_distributions')->cascadeOnDelete();
            $table->foreignUuid('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('disk');
            $table->string('path');
            $table->string('mime_type');
            $table->string('extension')->nullable();
            $table->unsignedBigInteger('size');
            $table->string('attachment_type')->default('transfer_proof');
            $table->timestamps();
        });

        Schema::create('fund_receipt_confirmations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('financial_submission_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('submission_disbursement_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('fund_distribution_id')->nullable()->constrained('submission_fund_distributions')->cascadeOnDelete();
            $table->foreignUuid('recipient_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('confirmed_by')->constrained('users')->restrictOnDelete();
            $table->decimal('amount', 18, 2);
            $table->timestamp('received_at');
            $table->text('notes')->nullable();
            $table->string('status')->default('confirmed');
            $table->timestamps();
            $table->unique('submission_disbursement_id');
            $table->unique('fund_distribution_id');
            $table->index(['recipient_user_id', 'status']);
        });

        Schema::create('fund_accountability_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('financial_submission_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignUuid('submitted_by')->constrained('users')->restrictOnDelete();
            $table->string('report_number')->unique();
            $table->string('status')->index();
            $table->decimal('received_amount', 18, 2);
            $table->decimal('realized_amount', 18, 2)->default(0);
            $table->decimal('remaining_amount', 18, 2)->default(0);
            $table->decimal('additional_amount', 18, 2)->default(0);
            $table->text('summary');
            $table->date('usage_date_from')->nullable();
            $table->date('usage_date_to')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignUuid('finance_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finance_reviewed_at')->nullable();
            $table->text('finance_notes')->nullable();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('fund_accountability_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('fund_accountability_report_id')->constrained()->cascadeOnDelete();
            $table->date('expense_date');
            $table->string('description');
            $table->foreignUuid('category_id')->nullable()->constrained('submission_request_types')->nullOnDelete();
            $table->string('category_name_snapshot')->nullable();
            $table->decimal('amount', 18, 2);
            $table->string('vendor_name')->nullable();
            $table->string('invoice_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('fund_accountability_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('fund_accountability_report_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('disk');
            $table->string('path');
            $table->string('mime_type');
            $table->string('extension')->nullable();
            $table->unsignedBigInteger('size');
            $table->string('attachment_type')->default('receipt');
            $table->timestamps();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE fund_receipt_confirmations ADD CONSTRAINT fund_receipt_source_check CHECK ((submission_disbursement_id IS NOT NULL AND fund_distribution_id IS NULL) OR (submission_disbursement_id IS NULL AND fund_distribution_id IS NOT NULL))');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fund_accountability_attachments');
        Schema::dropIfExists('fund_accountability_items');
        Schema::dropIfExists('fund_accountability_reports');
        Schema::dropIfExists('fund_receipt_confirmations');
        Schema::dropIfExists('fund_distribution_attachments');
        Schema::dropIfExists('submission_fund_distributions');
        Schema::table('submission_disbursements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recipient_user_id');
            $table->dropConstrainedForeignId('recipient_cooperative_id');
            $table->dropConstrainedForeignId('source_company_bank_account_id');
            $table->dropColumn(['recipient_type', 'recipient_name_snapshot', 'source_bank_name', 'source_account_number_snapshot', 'source_account_holder_snapshot', 'destination_bank_account_id', 'destination_reference_type', 'destination_reference_id', 'requires_distribution', 'distribution_status', 'received_by_recipient_at']);
        });
        Schema::dropIfExists('cooperative_bank_accounts');
        Schema::dropIfExists('company_bank_accounts');
    }
};
