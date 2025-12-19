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
        // Add new facility_type column
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->string('facility_type')->nullable()->after('facility_id');
        });

        // Migrate existing data: get facility type from facility_id
        $feedbacks = \DB::table('feedbacks')
            ->whereNotNull('facility_id')
            ->get();

        foreach ($feedbacks as $feedback) {
            $facility = \DB::table('facilities')
                ->where('id', $feedback->facility_id)
                ->first();
            
            if ($facility && $facility->type) {
                \DB::table('feedbacks')
                    ->where('id', $feedback->id)
                    ->update(['facility_type' => $facility->type]);
            }
        }

        // Drop foreign key constraint and index
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->dropForeign(['facility_id']);
            $table->dropIndex(['facility_id']);
        });

        // Drop facility_id column
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->dropColumn('facility_id');
        });

        // Add index on facility_type
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->index('facility_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add facility_id column back
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->foreignId('facility_id')->nullable()->after('user_id');
        });

        // Drop facility_type index
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->dropIndex(['facility_type']);
        });

        // Note: We cannot restore the exact facility_id values from facility_type
        // as multiple facilities can have the same type
        // So we'll just set them to null or leave them nullable

        // Add foreign key constraint back
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('set null');
            $table->index('facility_id');
        });

        // Drop facility_type column
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->dropColumn('facility_type');
        });
    }
};
