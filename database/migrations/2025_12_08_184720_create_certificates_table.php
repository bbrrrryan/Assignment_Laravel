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
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('reward_id')->nullable()->constrained()->onDelete('set null');
            $table->string('certificate_number')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('issued_date');
            $table->date('expiry_date')->nullable();
            $table->string('issued_by')->nullable(); // Admin who issued it
            $table->enum('status', ['pending', 'approved', 'issued', 'revoked'])->default('pending');
            $table->string('file_path')->nullable(); // PDF certificate file
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('certificate_number');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
