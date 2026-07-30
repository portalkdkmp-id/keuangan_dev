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
        Schema::create('submission_revision_responses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('revision_request_id')->unique()->constrained('submission_revision_requests')->cascadeOnDelete();
            $table->foreignUuid('financial_submission_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('responded_by')->constrained('users')->restrictOnDelete();
            $table->text('message')->nullable();
            $table->jsonb('change_summary')->nullable();
            $table->timestamp('responded_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submission_revision_responses');
    }
};
