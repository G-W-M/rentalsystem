<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Additive: the Unit model declares SoftDeletes, which needs a nullable
| deleted_at column. Guarded so it is safe to re-run.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            if (! Schema::hasColumn('units', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            if (Schema::hasColumn('units', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};