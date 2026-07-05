<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'user_id')->onDelete('cascade');
            $table->foreignId('unit_id')->constrained('units')->onDelete('cascade');
            $table->foreignId('property_id')->nullable()->constrained('properties')->nullOnDelete();
            $table->enum('category', ['plumbing', 'electrical', 'structural', 'appliance', 'pest', 'security', 'other'])->default('other');
            $table->text('description');
            $table->string('subject', 255)->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'emergency'])->default('medium');
            $table->enum('status', ['submitted', 'assigned', 'in_progress', 'resolved', 'rejected'])->default('submitted');
            $table->boolean('is_major')->default(false);
            $table->decimal('cost_estimate', 10, 2)->nullable();
            $table->decimal('actual_cost', 10, 2)->nullable();
            $table->string('before_photo')->nullable();
            $table->string('after_photo')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('caretakers', 'user_id')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->boolean('approved_by_landlord')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('unit_id');
            $table->index('property_id');
            $table->index('category');
            $table->index('status');
            $table->index('priority');
            $table->index('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_requests');
    }
};
