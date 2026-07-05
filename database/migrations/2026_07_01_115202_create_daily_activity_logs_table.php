<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caretaker_id')->constrained('caretakers', 'user_id')->onDelete('cascade');
            $table->date('log_date');
            $table->text('activities_performed');
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['caretaker_id', 'log_date']);
            $table->index('log_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_activity_logs');
    }
};
