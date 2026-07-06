<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DB-level safeguard against the tenant/unit double-allocation race.
 *
 * The application-level check in AllocateTenantRequest can race under
 * concurrent requests: two allocations could both pass validation before
 * either commits. This migration adds a MySQL generated column that is
 * non-null ONLY when is_current = 1, then a unique index on that column.
 * Since NULL values are not considered equal by MySQL's unique index, only
 * one row per tenant_id (and separately, per unit_id) can have is_current = 1
 * at a time — a genuine database-enforced guarantee, not just app logic.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tenant_occupancies', 'current_tenant_guard')) {
            DB::statement("
                ALTER TABLE tenant_occupancies
                ADD COLUMN current_tenant_guard INT GENERATED ALWAYS AS
                    (CASE WHEN is_current = 1 THEN tenant_id ELSE NULL END) STORED
            ");
        }

        if (! Schema::hasColumn('tenant_occupancies', 'current_unit_guard')) {
            DB::statement("
                ALTER TABLE tenant_occupancies
                ADD COLUMN current_unit_guard INT GENERATED ALWAYS AS
                    (CASE WHEN is_current = 1 THEN unit_id ELSE NULL END) STORED
            ");
        }

        DB::statement("
            ALTER TABLE tenant_occupancies
            ADD UNIQUE INDEX uniq_current_tenant (current_tenant_guard)
        ");

        DB::statement("
            ALTER TABLE tenant_occupancies
            ADD UNIQUE INDEX uniq_current_unit (current_unit_guard)
        ");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE tenant_occupancies DROP INDEX uniq_current_tenant');
        DB::statement('ALTER TABLE tenant_occupancies DROP INDEX uniq_current_unit');
        DB::statement('ALTER TABLE tenant_occupancies DROP COLUMN current_tenant_guard');
        DB::statement('ALTER TABLE tenant_occupancies DROP COLUMN current_unit_guard');
    }
};
