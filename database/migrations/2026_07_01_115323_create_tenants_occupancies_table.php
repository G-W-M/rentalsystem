<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenant_occupancies', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('tenant_id')
                ->constrained('tenants', 'user_id')
                ->cascadeOnDelete();

            $table->foreignId('unit_id')
                ->constrained('units')
                ->cascadeOnDelete();

            // Occupancy period
            $table->date('start_date');
            $table->date('end_date')->nullable();

            // Current occupancy flag
            $table->boolean('is_current')->default(true);

            // Lease details
            $table->decimal('rent_amount_at_start', 10, 2)->nullable();
            $table->string('lease_agreement_path')->nullable();

            // Deposit information
            $table->boolean('deposit_paid')->default(false);
            $table->decimal('deposit_amount', 10, 2)->nullable();
            $table->boolean('deposit_refunded')->default(false);

            // Tenancy termination
            $table->string('termination_reason')->nullable();

            // User who created the record
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Indexes
            $table->index('tenant_id');
            $table->index('unit_id');
            $table->index('is_current');
            $table->index('start_date');
            $table->index('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_occupancies');
    }
};
