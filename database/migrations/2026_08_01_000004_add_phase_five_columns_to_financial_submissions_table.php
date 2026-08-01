<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_submissions', function (Blueprint $table) {
            $table->foreignUuid('director_reviewed_by')->nullable()->after('bank_account_holder_snapshot')->constrained('users')->nullOnDelete();
            $table->timestamp('director_review_started_at')->nullable()->after('director_reviewed_by')->index();
            $table->foreignUuid('director_decided_by')->nullable()->after('director_review_started_at')->constrained('users')->nullOnDelete();
            $table->timestamp('director_decided_at')->nullable()->after('director_decided_by')->index();
            $table->decimal('director_approved_amount', 18, 2)->nullable()->after('director_decided_at');
            $table->integer('director_revision_count')->default(0)->after('director_approved_amount')->index();
            $table->timestamp('last_director_revision_requested_at')->nullable()->after('director_revision_count');
            $table->timestamp('last_director_resubmitted_at')->nullable()->after('last_director_revision_requested_at');
            $table->string('disbursement_status')->nullable()->after('last_director_resubmitted_at')->index();
            $table->timestamp('disbursed_at')->nullable()->after('disbursement_status')->index();
            $table->decimal('disbursed_amount', 18, 2)->nullable()->after('disbursed_at');
            $table->foreignUuid('disbursed_by')->nullable()->after('disbursed_amount')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('financial_submissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('director_reviewed_by');
            $table->dropConstrainedForeignId('director_decided_by');
            $table->dropConstrainedForeignId('disbursed_by');
            $table->dropColumn([
                'director_review_started_at',
                'director_decided_at',
                'director_approved_amount',
                'director_revision_count',
                'last_director_revision_requested_at',
                'last_director_resubmitted_at',
                'disbursement_status',
                'disbursed_at',
                'disbursed_amount',
            ]);
        });
    }
};
