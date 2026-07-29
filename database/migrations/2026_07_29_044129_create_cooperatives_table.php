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
        Schema::create('cooperatives', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nik')->unique();
            $table->string('name')->index();
            $table->foreignUuid('province_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('city_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('district_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('village_id')->constrained()->restrictOnDelete();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('cooperative_user_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('cooperative_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->unique(['cooperative_id', 'user_id']);
        });

        DB::statement('CREATE UNIQUE INDEX cooperative_user_assignments_one_primary ON cooperative_user_assignments (cooperative_id) WHERE is_primary = true');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cooperative_user_assignments');
        Schema::dropIfExists('cooperatives');
    }
};
