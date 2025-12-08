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
        Schema::create('loyalty_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('points')->default(0);
            $table->string('action_type'); // facility_booking, event_attendance, feedback_submission, etc.
            $table->foreignId('related_id')->nullable(); // ID of related booking, feedback, etc.
            $table->string('related_type')->nullable(); // Model class name
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('action_type');
            $table->index(['related_id', 'related_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_points');
    }
};
