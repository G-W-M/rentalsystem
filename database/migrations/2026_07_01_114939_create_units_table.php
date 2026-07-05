<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->onDelete('cascade');
            $table->string('unit_number', 20);
            $table->decimal('rent_amount', 10, 2);
            $table->enum('status', ['available', 'occupied', 'maintenance', 'unavailable'])->default('available');
            $table->timestamps();

            $table->unique(['property_id', 'unit_number']);
            $table->index('status');
            $table->index('property_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
