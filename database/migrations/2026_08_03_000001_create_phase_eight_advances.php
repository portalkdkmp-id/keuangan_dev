<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advance_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('financial_submission_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignUuid('requester_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('responsible_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('cooperative_id')->nullable()->constrained()->nullOnDelete();
            $table->index('requester_id');
            $table->index('responsible_user_id');
            $table->index('cooperative_id');
            $table->text('purpose');
            $table->decimal('estimated_amount', 18, 2);
            $table->decimal('approved_amount', 18, 2)->nullable();
            $table->decimal('disbursed_amount', 18, 2)->nullable();
            $table->date('expected_transaction_date')->nullable();
            $table->date('expected_settlement_date')->index();
            $table->unsignedInteger('settlement_due_days')->nullable();
            $table->foreignUuid('recipient_bank_account_id')->nullable()->constrained('user_bank_accounts')->nullOnDelete();
            $table->string('recipient_bank_name_snapshot')->nullable();
            $table->string('recipient_account_number_snapshot')->nullable();
            $table->string('recipient_account_holder_snapshot')->nullable();
            $table->string('advance_status')->index();
            $table->timestamp('disbursed_at')->nullable()->index();
            $table->timestamp('settled_at')->nullable();
            $table->timestamp('closed_at')->nullable()->index();
            $table->string('source_type')->default('manual');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('fund_accountability_reports', function (Blueprint $table) {
            $table->string('source_type')->default('fund_submission')->after('financial_submission_id')->index();
            $table->foreignUuid('advance_detail_id')->nullable()->unique()->after('source_type')->constrained('advance_details')->cascadeOnDelete();
        });
        Schema::table('fund_accountability_items', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('invoice_number');
            $table->string('payment_reference')->nullable()->after('payment_method');
        });
        Schema::table('fund_accountability_attachments', function (Blueprint $table) {
            $table->foreignUuid('fund_accountability_item_id')->nullable()->after('fund_accountability_report_id')->constrained('fund_accountability_items')->cascadeOnDelete();
        });
        Schema::table('fund_returns', function (Blueprint $table) {
            $table->string('source_type')->default('accountability')->after('fund_accountability_report_id')->index();
            $table->foreignUuid('source_advance_detail_id')->nullable()->after('source_type')->constrained('advance_details')->restrictOnDelete();
        });
        Schema::table('reimbursement_details', function (Blueprint $table) {
            $table->foreignUuid('source_advance_detail_id')->nullable()->after('source_accountability_report_id')->constrained('advance_details')->restrictOnDelete();
        });

        if (! DB::table('submission_request_categories')->where('code', 'advance')->exists()) {
            DB::table('submission_request_categories')->insert(['id' => (string) Str::uuid(), 'code' => 'advance', 'name' => 'Uang Panjar', 'slug' => 'uang-panjar', 'is_active' => true, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()]);
        }
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE advance_details ADD CONSTRAINT advance_amount_check CHECK (estimated_amount > 0 AND (approved_amount IS NULL OR approved_amount > 0) AND (disbursed_amount IS NULL OR disbursed_amount > 0))');
        }
    }

    public function down(): void
    {
        Schema::table('reimbursement_details', fn (Blueprint $table) => $table->dropConstrainedForeignId('source_advance_detail_id'));
        Schema::table('fund_returns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_advance_detail_id');
            $table->dropColumn('source_type');
        });
        Schema::table('fund_accountability_attachments', fn (Blueprint $table) => $table->dropConstrainedForeignId('fund_accountability_item_id'));
        Schema::table('fund_accountability_items', fn (Blueprint $table) => $table->dropColumn(['payment_method', 'payment_reference']));
        Schema::table('fund_accountability_reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('advance_detail_id');
            $table->dropColumn('source_type');
        });
        Schema::dropIfExists('advance_details');
    }
};
