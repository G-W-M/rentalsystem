<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key', 100)->unique();
            $table->text('setting_value');
            $table->string('setting_group', 100)->default('general');
            $table->enum('setting_type', ['string', 'integer', 'boolean', 'json', 'array'])->default('string');
            $table->boolean('is_public')->default(false);
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('setting_key');
            $table->index('setting_group');
            $table->index('is_public');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
