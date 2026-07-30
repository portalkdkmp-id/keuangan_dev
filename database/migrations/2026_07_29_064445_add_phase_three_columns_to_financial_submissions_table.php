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
            $table->foreignUuid('finance_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('finance_validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finance_validated_at')->nullable()->index();
            $table->foreignUuid('forwarded_to_approval_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('forwarded_to_approval_at')->nullable()->index();
            $table->integer('revision_count')->default(0)->index();
            $table->timestamp('last_revision_requested_at')->nullable();
            $table->timestamp('last_resubmitted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_submissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('finance_reviewed_by');
            $table->dropConstrainedForeignId('finance_validated_by');
            $table->dropConstrainedForeignId('forwarded_to_approval_by');
            $table->dropColumn([
                'finance_validated_at',
                'forwarded_to_approval_at',
                'revision_count',
                'last_revision_requested_at',
                'last_resubmitted_at',
            ]);
        });
    }
};
