<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landlords', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained('users')->onDelete('cascade');
            $table->string('company_name', 100)->nullable();
            $table->string('id_number', 50)->nullable();
            $table->string('kra_pin', 50)->nullable();
            $table->text('physical_address')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verification_date')->nullable();
            $table->unsignedInteger('max_properties')->default(10);
            $table->timestamp('registration_date')->nullable();
            $table->timestamps();

            $table->index('is_verified');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landlords');
    }
};
