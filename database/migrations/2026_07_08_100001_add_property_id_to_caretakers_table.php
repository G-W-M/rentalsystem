<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a nullable property_id to caretakers, representing which single
 * property this caretaker is responsible for (1:1 — one caretaker per
 * property, per product decision). Nullable because a caretaker can exist
 * before being assigned to a property (e.g. newly registered, awaiting
 * assignment).
 *
 * A UNIQUE index on property_id enforces "one caretaker per property" at
 * the database level — no two caretakers can share the same property_id
 * (NULLs are exempt from uniqueness in MySQL, so multiple unassigned
 * caretakers are fine).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caretakers', function (Blueprint $table) {
            if (! Schema::hasColumn('caretakers', 'property_id')) {
                $table->unsignedBigInteger('property_id')->nullable()->after('landlord_id');
                $table->foreign('property_id')->references('id')->on('properties')->nullOnDelete();
                $table->unique('property_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('caretakers', function (Blueprint $table) {
            $table->dropForeign(['property_id']);
            $table->dropUnique(['property_id']);
            $table->dropColumn('property_id');
        });
    }
};