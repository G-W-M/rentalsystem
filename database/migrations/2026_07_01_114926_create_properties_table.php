<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landlord_id')
                  ->constrained('landlords', 'user_id')
                  ->onDelete('cascade');

            $table->string('name', 100);
            $table->text('address');

            $table->enum('property_type', [
                'apartment',
                'house',
                'commercial',
                'office'
            ])->default('apartment');

            $table->enum('status', [
                'active',
                'inactive',
                'maintenance'
            ])->default('active');

            $table->text('description')->nullable();

            $table->timestamps();

            $table->index('landlord_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};