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
        Schema::create('financial_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('submission_number')->unique();
            $table->string('type')->index();
            $table->string('status')->index();
            $table->foreignUuid('cooperative_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('submitted_by')->constrained('users')->restrictOnDelete();
            $table->string('current_assignee_role')->nullable()->index();
            $table->string('title', 200);
            $table->text('purpose');
            $table->date('needed_date')->nullable()->index();
            $table->text('notes')->nullable();
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->timestamp('submitted_at')->nullable()->index();
            $table->timestamp('finance_review_started_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index('created_at');
            $table->index(['status', 'current_assignee_role']);
            $table->index(['cooperative_id', 'created_at']);
            $table->index(['submitted_by', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_submissions');
    }
};
