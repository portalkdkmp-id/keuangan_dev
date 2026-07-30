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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignUuid('city_id')->nullable()->after('phone')->constrained()->nullOnDelete();
        });

        Schema::table('financial_submissions', function (Blueprint $table) {
            $table->foreignUuid('submitter_city_id')->nullable()->after('submitted_by')->constrained('cities')->nullOnDelete();
            $table->foreignUuid('submission_request_category_id')->nullable()->after('type')->constrained()->nullOnDelete();
            $table->foreignUuid('submission_request_type_id')->nullable()->after('submission_request_category_id')->constrained()->nullOnDelete();
            $table->foreignUuid('recipient_bank_account_id')->nullable()->after('cooperative_id')->constrained('user_bank_accounts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_submissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recipient_bank_account_id');
            $table->dropConstrainedForeignId('submission_request_type_id');
            $table->dropConstrainedForeignId('submission_request_category_id');
            $table->dropConstrainedForeignId('submitter_city_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('city_id');
        });
    }
};
