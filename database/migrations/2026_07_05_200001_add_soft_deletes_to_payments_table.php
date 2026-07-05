<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Additive migration. The Payment model declares SoftDeletes, which requires a
| nullable deleted_at column. The base schema (rental-system.sql) does not
| include it, so this adds it without altering the original migration history.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};