<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Developer B domain
|--------------------------------------------------------------------------
| Loaded under the /api prefix by bootstrap/app.php.
| Developer A appends the landlord property/unit/tenant resource routes to
| this same file. The 'maintenance approve/reject' endpoints below are
| landlord-scoped but call the Dev-B-owned MaintenanceController.
*/

// ----- Public -----
Route::post('/login', [AuthController::class, 'login']);

// ----- Authenticated (any active role) -----
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/notifications', [NotificationsController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationsController::class, 'unreadCount']);
    Route::post('/notifications/{notification}/read', [NotificationsController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationsController::class, 'markAllRead']);
});

// ----- Landlord / Admin (maintenance approvals owned by Dev B) -----
Route::middleware(['auth:sanctum', 'role:landlord,admin', 'activity'])
    ->prefix('landlord')
    ->group(function () {
        Route::post('/maintenance/{maintenance}/approve', [MaintenanceController::class, 'approve']);
        Route::post('/maintenance/{maintenance}/reject', [MaintenanceController::class, 'reject']);
    });

// ----- Caretaker -----
Route::middleware(['auth:sanctum', 'role:caretaker', 'activity'])
    ->prefix('caretaker')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'caretaker']);

        Route::get('/tasks', [TaskController::class, 'index']);
        Route::post('/tasks/{task}/start', [TaskController::class, 'start']);
        Route::post('/tasks/{task}/complete', [TaskController::class, 'complete']);

        Route::post('/payments/{payment}/verify', [PaymentController::class, 'verify']);
        Route::get('/properties', [\App\Http\Controllers\CaretakerController::class, 'properties']);
    });

// ----- Tenant -----
Route::middleware(['auth:sanctum', 'role:tenant', 'activity', 'offline.sync'])
    ->prefix('tenant')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'tenant']);

        Route::post('/maintenance', [MaintenanceController::class, 'store']);
        Route::post('/maintenance/{maintenance}/confirm', [MaintenanceController::class, 'confirm']);

        Route::get('/payments', [PaymentController::class, 'tenantHistory']);
        Route::post('/payments/{payment}/transaction-code', [PaymentController::class, 'submitTransactionCode']);
    });
