<?php
use App\Http\Controllers\TaskController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\MaintenanceController;
 
Route::middleware(['auth:sanctum', 'role:caretaker'])
    ->prefix('caretaker')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'caretaker']);
        Route::get('/tasks', [TaskController::class, 'index']);
        Route::post('/tasks/{task}/start', [TaskController::class, 'start']);
        Route::post('/tasks/{task}/complete', [TaskController::class, 'complete']);
        Route::post('/payments/{payment}/verify', [PaymentController::class, 'verify']);
    });