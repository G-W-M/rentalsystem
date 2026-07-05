<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained('users')->onDelete('cascade');
            $table->foreignId('landlord_id')->nullable()->constrained('landlords', 'user_id')->nullOnDelete();
            $table->string('id_number', 50)->nullable();
            $table->string('nationality', 100)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('emergency_contact', 100)->nullable();
            $table->string('emergency_phone', 20)->nullable();
            $table->enum('employment_status', ['employed', 'self-employed', 'student', 'retired'])->nullable();
            $table->string('employer_name', 100)->nullable();
            $table->string('employer_phone', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('moved_in_date')->nullable();
            $table->date('moved_out_date')->nullable();
            $table->timestamps();

            $table->index('landlord_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
