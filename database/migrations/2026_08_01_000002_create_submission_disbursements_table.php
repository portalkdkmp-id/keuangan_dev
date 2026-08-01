<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_disbursements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('financial_submission_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('director_review_id')->constrained('submission_director_reviews')->cascadeOnDelete();
            $table->string('disbursement_number')->unique();
            $table->foreignUuid('disbursed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 18, 2);
            $table->string('payment_method');
            $table->string('bank_name')->nullable();
            $table->string('source_account_name')->nullable();
            $table->string('source_account_number_masked')->nullable();
            $table->string('destination_bank_snapshot');
            $table->string('destination_account_number_snapshot');
            $table->string('destination_account_holder_snapshot');
            $table->string('transaction_reference')->nullable();
            $table->date('transfer_date');
            $table->timestamp('transferred_at');
            $table->text('notes')->nullable();
            $table->string('status')->index();
            $table->timestamps();

            $table->unique('financial_submission_id');
            $table->index(['disbursed_by', 'transferred_at']);
            $table->index(['payment_method', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_disbursements');
    }
};
