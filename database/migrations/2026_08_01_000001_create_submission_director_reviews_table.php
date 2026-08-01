<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_director_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('financial_submission_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('director_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('review_number')->default(1);
            $table->string('status')->index();
            $table->string('decision')->nullable()->index();
            $table->decimal('approved_amount', 18, 2)->nullable();
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->string('revision_subject')->nullable();
            $table->text('revision_message')->nullable();
            $table->jsonb('revision_fields')->nullable();
            $table->text('change_summary')->nullable();
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('decided_at')->nullable()->index();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->unique(['financial_submission_id', 'review_number']);
            $table->index('financial_submission_id');
            $table->index(['status', 'created_at']);
            $table->index(['director_id', 'status']);
            $table->index(['decision', 'decided_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_director_reviews');
    }
};
