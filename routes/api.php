<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Developer B domain
|--------------------------------------------------------------------------
*/

// ----- Public -----
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// ----- Authenticated (any active role) -----
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me', [AuthController::class, 'updateProfile']);
    Route::put('/me/password', [AuthController::class, 'updatePassword']);

    Route::get('/notifications', [NotificationsController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationsController::class, 'unreadCount']);
    Route::post('/notifications/{notification}/read', [NotificationsController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationsController::class, 'markAllRead']);

    // Theme is readable by everyone so it applies across all roles' UI.
    Route::get('/settings/theme', [SettingsController::class, 'getTheme']);
});

// ----- Admin only -----
Route::middleware(['auth:sanctum', 'role:admin', 'activity'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'admin']);

        Route::get('/users', [AdminController::class, 'index']);
        Route::post('/users', [AdminController::class, 'store']);
        Route::put('/users/{user}', [AdminController::class, 'update']);
        Route::delete('/users/{user}', [AdminController::class, 'destroy']);
        Route::post('/users/{user}/reset-password', [AdminController::class, 'resetPassword']);

        Route::get('/sessions', [ActivityLogController::class, 'sessions']);
        Route::get('/audit-trails', [ActivityLogController::class, 'auditTrails']);
        Route::get('/caretaker-activity', [ActivityLogController::class, 'caretakerActivity']);

        Route::put('/settings/theme', [SettingsController::class, 'updateTheme']);

        Route::get('/payments', [PaymentController::class, 'adminIndex']);
        Route::get('/maintenance', [MaintenanceController::class, 'adminIndex']);
        Route::get('/payments/export/csv', [ExportController::class, 'paymentsCsv']);
        Route::get('/payments/export/pdf', [ExportController::class, 'paymentsPdf']);
        Route::get('/maintenance/export/csv', [ExportController::class, 'maintenanceCsv']);
        Route::get('/maintenance/export/pdf', [ExportController::class, 'maintenancePdf']);
    });

// ----- Landlord / Admin (maintenance approvals + exports owned by Dev B) -----
Route::middleware(['auth:sanctum', 'role:landlord,admin', 'activity'])
    ->prefix('landlord')
    ->group(function () {
        Route::post('/maintenance/{maintenance}/approve', [MaintenanceController::class, 'approve']);
        Route::post('/maintenance/{maintenance}/reject', [MaintenanceController::class, 'reject']);

        Route::get('/payments/export/csv', [ExportController::class, 'paymentsCsv']);
        Route::get('/payments/export/pdf', [ExportController::class, 'paymentsPdf']);
        Route::get('/maintenance/export/csv', [ExportController::class, 'maintenanceCsv']);
        Route::get('/maintenance/export/pdf', [ExportController::class, 'maintenancePdf']);
    });

// ----- Caretaker -----
Route::middleware(['auth:sanctum', 'role:caretaker', 'activity'])
    ->prefix('caretaker')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'caretaker']);

        Route::get('/tasks', [TaskController::class, 'index']);
        Route::post('/tasks/{task}/start', [TaskController::class, 'start']);
        Route::post('/tasks/{task}/complete', [TaskController::class, 'complete']);
        Route::get('/payments/pending', [PaymentController::class, 'pendingForCaretaker']);
Route::post('/payments/{payment}/verify', [PaymentController::class, 'verify']);
Route::get('/payments/verified', [PaymentController::class, 'caretakerVerifiedIndex']);
        Route::post('/payments/{payment}/verify', [PaymentController::class, 'verify']);
        Route::get('/payments/verified', [PaymentController::class, 'caretakerVerifiedIndex']);

        Route::get('/properties', [\App\Http\Controllers\CaretakerController::class, 'properties']);
        Route::get('/units', [\App\Http\Controllers\CaretakerController::class, 'units']);
        Route::get('/activity-logs', [\App\Http\Controllers\DailyActivityLogController::class, 'index']);
        Route::post('/activity-logs', [\App\Http\Controllers\DailyActivityLogController::class, 'store']);
    });

// ----- Tenant -----
Route::middleware(['auth:sanctum', 'role:tenant', 'activity', 'offline.sync'])
    ->prefix('tenant')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'tenant']);
        Route::post('/payments/init-and-submit', [PaymentController::class, 'initAndSubmit']);
        Route::get('/pay-rent', [PaymentController::class, 'payRent']);
        Route::get('/payments', [PaymentController::class, 'tenantHistory']);
        Route::post('/payments/{payment}/submit', [PaymentController::class, 'submitPayment']);
        Route::post('/payments/{payment}/transaction-code', [PaymentController::class, 'submitTransactionCode']);

        Route::get('/maintenance', [MaintenanceController::class, 'tenantIndex']);
        Route::post('/maintenance', [MaintenanceController::class, 'store']);
        Route::post('/maintenance/{maintenance}/confirm', [MaintenanceController::class, 'confirm']);
    });