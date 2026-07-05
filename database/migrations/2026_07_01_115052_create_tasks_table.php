<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_request_id')->constrained('maintenance_requests')->onDelete('cascade');
            $table->foreignId('assigned_to')->constrained('caretakers', 'user_id')->onDelete('cascade');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('task_description', 255);
            $table->enum('priority', ['low', 'medium', 'high', 'emergency'])->default('medium');
            $table->date('due_date')->nullable();
            $table->enum('status', ['assigned', 'in_progress', 'completed', 'cancelled'])->default('assigned');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('completion_notes')->nullable();
            $table->string('completion_photo')->nullable();
            $table->boolean('tenant_confirmed')->default(false);
            $table->boolean('is_completed_by_caretaker')->default(false);
            $table->timestamps();

            $table->index('maintenance_request_id');
            $table->index('assigned_to');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
