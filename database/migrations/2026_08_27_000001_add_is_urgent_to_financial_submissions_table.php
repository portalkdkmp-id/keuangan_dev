<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_submissions', function (Blueprint $table) {
            $table->boolean('is_urgent')->default(false)->index();
        });
    }

    public function down(): void
    {
        Schema::table('financial_submissions', function (Blueprint $table) {
            $table->dropColumn('is_urgent');
        });
    }
};
