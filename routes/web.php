<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;
/*
|--------------------------------------------------------------------------
| Web Routes — Developer B domain
|--------------------------------------------------------------------------
*/

// In routes/web.php
Route::view('/', 'welcome')->name('home');
// ----- Auth pages -----
Route::view('/login', 'auth.login')->name('login');
Route::view('/register', 'auth.register')->name('register');
Route::view('/forgot-password', 'auth.forgot-password')->name('password.request');
Route::view('/reset-password', 'auth.reset-password')->name('password.reset');

// ----- Session logout (web) -----
Route::post('/logout', function (Request $request) {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/login');
})->name('logout')->middleware('auth');

// ----- Authenticated portal pages (Dev B roles) -----
Route::middleware('auth')->group(function () {
    // Admin
    Route::view('/admin/dashboard', 'admin.dashboard')->name('admin.dashboard');
    Route::view('/admin/users', 'admin.users')->name('admin.users');
    Route::view('/admin/settings', 'admin.settings')->name('admin.settings');
    Route::view('/admin/activity-logs', 'admin.activity-logs')->name('admin.activity-logs');

    // Caretaker
    Route::view('/caretaker/dashboard', 'caretaker.dashboard')->name('caretaker.dashboard');
    Route::view('/caretaker/tasks', 'caretaker.tasks')->name('caretaker.tasks.index');
    Route::view('/caretaker/maintenance', 'caretaker.maintenance')->name('caretaker.maintenance.index');
    Route::view('/caretaker/payments', 'caretaker.payments')->name('caretaker.payments.index');
    Route::view('/caretaker/verified-payments', 'caretaker.verified-payments')->name('caretaker.verified-payments');
    Route::view('/caretaker/settings', 'caretaker.settings.settings')->name('caretaker.settings');
    Route::view('/caretaker/properties', 'caretaker.properties')->name('caretaker.properties');
    Route::view('/caretaker/activity-logs', 'caretaker.activity-logs')->name('caretaker.activity-logs');

    // Tenant
    Route::middleware(['auth', 'role:tenant'])->group(function () {
    Route::view('/tenant/dashboard', 'tenant.dashboard')->name('tenant.dashboard');
    Route::view('/tenant/unit', 'tenant.unit')->name('tenant.unit');
    Route::view('/tenant/maintenance', 'tenant.maintenance-request')->name('tenant.maintenance');
    Route::view('/tenant/payments', 'tenant.payments')->name('tenant.payments');
    Route::view('/tenant/settings', 'tenant.settings.settings')->name('tenant.settings');

    Route::get('/tenant/pay-rent', [PaymentController::class, 'payRent'])->name('tenant.pay-rent');
    Route::post('/tenant/payments/{payment}/submit', [PaymentController::class, 'submitPayment'])->name('tenant.payments.submit');
    Route::get('/tenant/payments/{payment}/receipt', [PaymentController::class, 'downloadReceipt'])->name('tenant.payments.receipt');
});
});
