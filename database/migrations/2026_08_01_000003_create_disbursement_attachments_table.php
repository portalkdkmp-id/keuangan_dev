<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disbursement_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('submission_disbursement_id')->constrained('submission_disbursements')->cascadeOnDelete();
            $table->foreignUuid('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('disk');
            $table->string('path');
            $table->string('mime_type');
            $table->string('extension')->nullable();
            $table->unsignedBigInteger('size');
            $table->string('attachment_type')->index();
            $table->timestamps();

            $table->index('submission_disbursement_id');
            $table->index(['uploaded_by', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disbursement_attachments');
    }
};
