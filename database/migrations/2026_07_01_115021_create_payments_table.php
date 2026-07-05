<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'user_id')->onDelete('cascade');
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->date('payment_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->string('transaction_id', 100)->nullable();
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->text('receipt_url')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('unit_id');
            $table->index('payment_date');
            $table->index('status');
            $table->unique('transaction_id');
            $table->index('verified_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
