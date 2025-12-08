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
        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique(); // e.g., LAB-101, GYM-A
            $table->text('description')->nullable();
            $table->enum('type', ['classroom', 'laboratory', 'sports', 'auditorium', 'library', 'cafeteria', 'other'])->default('other');
            $table->string('location'); // Building and floor
            $table->integer('capacity');
            $table->json('available_times')->nullable(); // Default available hours
            $table->json('equipment')->nullable(); // List of equipment available
            $table->text('rules')->nullable(); // Facility rules/guidelines
            $table->enum('status', ['available', 'maintenance', 'unavailable', 'reserved'])->default('available');
            $table->string('image_url')->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->integer('booking_advance_days')->default(30); // How many days in advance can be booked
            $table->integer('max_booking_hours')->default(4); // Maximum hours per booking
            $table->timestamps();
            
            $table->index('type');
            $table->index('status');
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facilities');
    }
};
