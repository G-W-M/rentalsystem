<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LandlordController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Landlord API Routes — Developer A domain
|--------------------------------------------------------------------------
| MERGE-SAFE: this is a SEPARATE partial file. Load it from bootstrap/app.php
| alongside routes/api.php:
|
|     Route::middleware('api')->prefix('api')->group(function () {
|         require base_path('routes/api.php');           // Dev B
|         require base_path('routes/landlord_api.php');   // Dev A
|     });
|
| Both developers edit only their own route file — zero route conflicts.
| The maintenance approve/reject endpoints remain in Dev B's routes/api.php
| (they call the Dev-B-owned MaintenanceController).
*/

Route::middleware(['auth:sanctum', 'role:landlord,admin', 'activity'])
    ->prefix('landlord')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'landlord']);

        Route::apiResource('properties', PropertyController::class);
        Route::apiResource('units', UnitController::class);

        Route::get('/tenants', [TenantController::class, 'index']);
        Route::post('/tenants', [TenantController::class, 'store']);
        Route::post('/tenants/{tenant}/allocate', [TenantController::class, 'allocate']);
        Route::post('/tenants/{tenant}/deallocate', [TenantController::class, 'deallocate']);

        Route::get('/caretakers', [LandlordController::class, 'caretakers']);
        Route::post('/caretakers', [LandlordController::class, 'storeCaretaker']);
        Route::get('/payments', [\App\Http\Controllers\PaymentController::class, 'landlordIndex']);
    });
