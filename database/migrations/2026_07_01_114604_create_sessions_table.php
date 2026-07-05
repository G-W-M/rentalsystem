<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('session_token', 255)->unique();
            $table->enum('device_type', ['web', 'mobile', 'api'])->default('web');
            $table->timestamp('login_time')->useCurrent();
            $table->timestamp('logout_time')->nullable();
            $table->unsignedInteger('session_duration')->nullable();
            $table->longText('payload');
            $table->boolean('is_active')->default(true);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('location', 255)->nullable();
           $table->integer('last_activity')->index();
            $table->timestamps();

            $table->index('user_id');
            $table->index('login_time');
            $table->index('session_token');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
