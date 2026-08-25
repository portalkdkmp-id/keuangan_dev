<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submission_request_types', function (Blueprint $table) {
            $table->foreignUuid('submission_request_category_id')
                ->nullable()
                ->after('slug')
                ->constrained('submission_request_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('submission_request_types', function (Blueprint $table) {
            $table->dropConstrainedForeignId('submission_request_category_id');
        });
    }
};
