<?php
/* ============================ routes/landlord.php ============================
 * OWNER: Dev A. Everything a landlord (or admin) can do.
 */
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\DashboardController;

Route::middleware(['auth:sanctum', 'role:landlord,admin'])
    ->prefix('landlord')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'landlord']);

        Route::apiResource('properties', PropertyController::class);
        Route::apiResource('units', UnitController::class);

        // Tenant lifecycle (landlord-driven; no self-registration)
        Route::post('/tenants', [TenantController::class, 'store']);
        Route::post('/tenants/{tenant}/allocate', [TenantController::class, 'allocate']);
        Route::post('/tenants/{tenant}/deallocate', [TenantController::class, 'deallocate']);
        Route::get('/tenants', [TenantController::class, 'index']);
    });
