<?php
/**
 * Author: Liew Zi Li
 * Module: User Management Module
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');

            $table->string('phone_number')->nullable();
            $table->text('address')->nullable();

            $table->string('role')->default('student');

            $table->string('status')->default('active');

            $table->timestamp('last_login_at')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};