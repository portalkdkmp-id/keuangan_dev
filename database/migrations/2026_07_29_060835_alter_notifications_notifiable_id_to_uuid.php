<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('notifications') || DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS notifications_notifiable_type_notifiable_id_index');
        DB::statement('ALTER TABLE notifications DROP COLUMN notifiable_id');
        DB::statement('ALTER TABLE notifications ADD COLUMN notifiable_id uuid');
        DB::statement('CREATE INDEX notifications_notifiable_type_notifiable_id_index ON notifications (notifiable_type, notifiable_id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('notifications') || DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS notifications_notifiable_type_notifiable_id_index');
        DB::statement('ALTER TABLE notifications DROP COLUMN notifiable_id');
        DB::statement('ALTER TABLE notifications ADD COLUMN notifiable_id bigint');
        DB::statement('CREATE INDEX notifications_notifiable_type_notifiable_id_index ON notifications (notifiable_type, notifiable_id)');
    }
};
