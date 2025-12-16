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
        // Check if personal_id already exists (migration may have been run manually)
        if (Schema::hasColumn('users', 'personal_id')) {
            // Column already exists, skip migration
            return;
        }
        
        // Check if studentid exists and needs to be renamed
        if (Schema::hasColumn('users', 'studentid')) {
            // MariaDB/MySQL doesn't support renameColumn, use raw SQL
            \DB::statement('ALTER TABLE `users` CHANGE `studentid` `personal_id` VARCHAR(10) NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if studentid already exists
        if (Schema::hasColumn('users', 'studentid')) {
            // Column already exists, skip
            return;
        }
        
        // Check if personal_id exists and needs to be renamed back
        if (Schema::hasColumn('users', 'personal_id')) {
            // MariaDB/MySQL doesn't support renameColumn, use raw SQL
            \DB::statement('ALTER TABLE `users` CHANGE `personal_id` `studentid` VARCHAR(10) NULL');
        }
    }
};
