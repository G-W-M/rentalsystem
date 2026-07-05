<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caretaker_id')->constrained('caretakers', 'user_id')->onDelete('cascade');
            $table->foreignId('property_id')->nullable()->constrained('properties')->nullOnDelete();
            $table->date('log_date');
            $table->timestamp('log_time')->nullable();
            $table->enum('activity_type', ['inspection', 'cleaning', 'repair', 'meeting', 'reporting', 'other'])->default('other');
            $table->text('description');
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->enum('status', ['draft', 'submitted', 'reviewed'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('location', 255)->nullable();
            $table->string('photo_attachment')->nullable();
            $table->timestamps();

            $table->index('caretaker_id');
            $table->index('property_id');
            $table->index('log_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
