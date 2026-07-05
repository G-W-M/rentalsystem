<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('report_type', 50);
            $table->foreignId('generated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->date('date_range_start')->nullable();
            $table->date('date_range_end')->nullable();
            $table->json('filters')->nullable();
            $table->json('data')->nullable();
            $table->string('file_path', 255)->nullable();
            $table->enum('format', ['pdf', 'excel', 'csv'])->default('pdf');
            $table->timestamp('generated_at')->nullable();
            $table->boolean('is_scheduled')->default(false);
            $table->enum('schedule_frequency', ['daily', 'weekly', 'monthly'])->nullable();
            $table->timestamps();

            $table->index('report_type');
            $table->index('generated_at');
            $table->index('generated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
