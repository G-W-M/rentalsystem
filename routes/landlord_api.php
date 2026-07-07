<?php

use App\Http\Controllers\DailyActivityLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LandlordController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Landlord API Routes — Developer A domain
|--------------------------------------------------------------------------
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
        Route::put('/caretakers/{caretaker}/assign-property', [LandlordController::class, 'assignCaretakerToProperty']);

        Route::get('/payments', [PaymentController::class, 'landlordIndex']);
        Route::get('/maintenance', [\App\Http\Controllers\MaintenanceController::class, 'landlordIndex']);

        Route::get('/activity-logs', [DailyActivityLogController::class, 'landlordIndex']);
    });