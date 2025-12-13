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
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->enum('type', ['info', 'warning', 'success', 'error', 'reminder', 'general'])->default('info');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('target_audience', ['all', 'students', 'staff', 'admins', 'specific'])->default('all');
            $table->json('target_user_ids')->nullable(); // For specific users
            $table->timestamp('published_at')->nullable(); // When announcement was published
            $table->timestamp('expires_at')->nullable(); // When announcement expires
            $table->boolean('is_active')->default(true);
            $table->boolean('is_pinned')->default(false); // Pin important announcements to top
            $table->integer('views_count')->default(0); // Track how many times viewed
            $table->timestamps();
            
            $table->index('type');
            $table->index('target_audience');
            $table->index('published_at');
            $table->index('expires_at');
            $table->index('is_active');
            $table->index('is_pinned');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
