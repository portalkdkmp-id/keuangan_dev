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
        Schema::table('financial_submissions', function (Blueprint $table) {
            $table->foreignUuid('approval_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approval_review_started_at')->nullable()->index();
            $table->foreignUuid('approval_decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approval_decided_at')->nullable()->index();
            $table->decimal('approval_approved_amount', 18, 2)->nullable();
            $table->integer('approval_revision_count')->default(0)->index();
            $table->timestamp('last_approval_revision_requested_at')->nullable();
            $table->timestamp('last_approval_resubmitted_at')->nullable();
            $table->foreignUuid('forwarded_to_director_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('forwarded_to_director_at')->nullable()->index();
            $table->string('bank_name_snapshot')->nullable();
            $table->string('bank_account_number_snapshot')->nullable();
            $table->string('bank_account_holder_snapshot')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_submissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approval_reviewed_by');
            $table->dropConstrainedForeignId('approval_decided_by');
            $table->dropConstrainedForeignId('forwarded_to_director_by');
            $table->dropColumn([
                'approval_review_started_at',
                'approval_decided_at',
                'approval_approved_amount',
                'approval_revision_count',
                'last_approval_revision_requested_at',
                'last_approval_resubmitted_at',
                'forwarded_to_director_at',
                'bank_name_snapshot',
                'bank_account_number_snapshot',
                'bank_account_holder_snapshot',
            ]);
        });
    }
};
