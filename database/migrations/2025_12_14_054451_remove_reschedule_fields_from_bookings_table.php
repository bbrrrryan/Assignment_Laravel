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
        Schema::table('bookings', function (Blueprint $table) {
            // Drop foreign key first
            if (Schema::hasColumn('bookings', 'reschedule_processed_by')) {
                $table->dropForeign(['reschedule_processed_by']);
            }
            
            // Drop index
            $table->dropIndex(['reschedule_status']);
            
            // Drop columns
            $table->dropColumn([
                'reschedule_status',
                'requested_booking_date',
                'requested_start_time',
                'requested_end_time',
                'reschedule_reason',
                'reschedule_requested_at',
                'reschedule_processed_by',
                'reschedule_processed_at',
                'reschedule_rejection_reason',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Re-add reschedule request fields
            $table->enum('reschedule_status', ['none', 'pending', 'approved', 'rejected'])->default('none')->after('cancellation_reason');
            $table->date('requested_booking_date')->nullable()->after('reschedule_status');
            $table->time('requested_start_time')->nullable()->after('requested_booking_date');
            $table->time('requested_end_time')->nullable()->after('requested_start_time');
            $table->text('reschedule_reason')->nullable()->after('requested_end_time');
            $table->timestamp('reschedule_requested_at')->nullable()->after('reschedule_reason');
            $table->foreignId('reschedule_processed_by')->nullable()->constrained('users')->onDelete('set null')->after('reschedule_requested_at');
            $table->timestamp('reschedule_processed_at')->nullable()->after('reschedule_processed_by');
            $table->text('reschedule_rejection_reason')->nullable()->after('reschedule_processed_at');
            
            $table->index('reschedule_status');
        });
    }
};
