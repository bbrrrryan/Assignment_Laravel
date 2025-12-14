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
        Schema::table('user_announcement', function (Blueprint $table) {
            $table->boolean('is_starred')->default(false)->after('read_at');
            $table->timestamp('starred_at')->nullable()->after('is_starred');
            $table->index('is_starred');
        });

        Schema::table('user_notification', function (Blueprint $table) {
            $table->boolean('is_starred')->default(false)->after('acknowledged_at');
            $table->timestamp('starred_at')->nullable()->after('is_starred');
            $table->index('is_starred');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_announcement', function (Blueprint $table) {
            $table->dropIndex(['is_starred']);
            $table->dropColumn(['is_starred', 'starred_at']);
        });

        Schema::table('user_notification', function (Blueprint $table) {
            $table->dropIndex(['is_starred']);
            $table->dropColumn(['is_starred', 'starred_at']);
        });
    }
};
