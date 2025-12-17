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
        Schema::table('feedbacks', function (Blueprint $table) {
            if (Schema::hasColumn('feedbacks', 'booking_id')) {
                // Drop foreign key + column in a safe way
                $table->dropConstrainedForeignId('booking_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            if (!Schema::hasColumn('feedbacks', 'booking_id')) {
                $table->foreignId('booking_id')->nullable()->constrained()->onDelete('set null');
            }
        });
    }
};


