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
        Schema::table('finance_submission_details', function (Blueprint $table) {
            $table->timestamp('staff_reviewed_at')->nullable()->index();
            $table->text('rejection_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_submission_details', function (Blueprint $table) {
            $table->dropColumn(['staff_reviewed_at', 'rejection_reason']);
        });
    }
};
