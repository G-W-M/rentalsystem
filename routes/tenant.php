<? php
Route::middleware(['auth:sanctum', 'role:tenant'])
    ->prefix('tenant')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'tenant']);
        Route::post('/maintenance', [MaintenanceController::class, 'store']);
        Route::post('/maintenance/{request}/confirm', [MaintenanceController::class, 'confirm']);
        Route::get('/payments', [PaymentController::class, 'tenantHistory']);
        Route::post('/payments/{payment}/transaction-code', [PaymentController::class, 'submitTransactionCode']);
    });