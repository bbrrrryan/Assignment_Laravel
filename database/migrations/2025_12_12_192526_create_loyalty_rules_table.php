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
        Schema::create('loyalty_rules', function (Blueprint $table) {
            $table->id();
            $table->string('action_type')->unique(); // facility_booking, feedback_submission, event_attendance, etc.
            $table->string('name'); // Display name: "Facility Booking", "Feedback Submission"
            $table->text('description')->nullable();
            $table->integer('points')->default(0); // Points awarded for this action
            $table->boolean('is_active')->default(true);
            $table->json('conditions')->nullable(); // Additional conditions (e.g., minimum booking duration)
            $table->timestamps();
            
            $table->index('action_type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_rules');
    }
};
