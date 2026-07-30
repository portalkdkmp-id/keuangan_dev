<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('submission_revision_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('financial_submission_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('requested_by')->constrained('users')->restrictOnDelete();
            $table->integer('revision_number');
            $table->string('subject', 200);
            $table->text('message');
            $table->jsonb('fields')->nullable();
            $table->string('status')->index();
            $table->timestamp('requested_at');
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index('financial_submission_id');
        });

        DB::statement("CREATE UNIQUE INDEX submission_revision_requests_one_open ON submission_revision_requests (financial_submission_id) WHERE status = 'open'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS submission_revision_requests_one_open');
        Schema::dropIfExists('submission_revision_requests');
    }
};
