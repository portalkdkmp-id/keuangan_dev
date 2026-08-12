<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submission_items', function (Blueprint $table) {
            $table->foreignUuid('request_type_id')->nullable()->after('category_id')->constrained('submission_request_types')->nullOnDelete();
            $table->string('request_type_name')->nullable()->after('category_name');
            $table->string('other_type_name')->nullable()->after('request_type_name');
            $table->index('request_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('submission_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('request_type_id');
            $table->dropColumn(['request_type_name', 'other_type_name']);
        });
    }
};
