<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caretakers', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained('users')->onDelete('cascade');
            $table->foreignId('landlord_id')->constrained('landlords', 'user_id')->onDelete('cascade');

            // Add property_id with unique constraint
            $table->foreignId('property_id')
                  ->nullable()
                  ->after('landlord_id')
                  ->constrained('properties')
                  ->nullOnDelete();

            // This ensures one caretaker per property
            $table->unique('property_id');

            $table->string('id_number', 50)->nullable();
            $table->string('emergency_contact', 100)->nullable();
            $table->string('emergency_phone', 20)->nullable();
            $table->json('skills')->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('hire_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->decimal('rating', 3, 1)->nullable();
            $table->decimal('salary', 10, 2)->nullable();
            $table->timestamps();

            $table->index('landlord_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caretakers');
    }
};
