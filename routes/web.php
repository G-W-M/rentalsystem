<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
| Web Routes — Developer B domain (auth, caretaker, tenant).
| Landlord pages live in routes/landlord_web.php (Developer A), loaded
| separately from bootstrap/app.php. Do not add landlord routes here.
*/

Route::view('/', 'welcome')->name('home');

Route::view('/login', 'auth.login')->name('login');
Route::view('/forgot-password', 'auth.forgot-password')->name('password.request');
Route::view('/reset-password', 'auth.reset-password')->name('password.reset');

Route::post('/logout', function (Request $request) {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
})->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    // Caretaker
    Route::view('/caretaker/dashboard', 'caretaker.dashboard')->name('caretaker.dashboard');
    Route::view('/caretaker/tasks', 'caretaker.tasks')->name('caretaker.tasks.index');
    Route::view('/caretaker/maintenance', 'caretaker.maintenance')->name('caretaker.maintenance.index');
    Route::view('/caretaker/payments', 'caretaker.payments')->name('caretaker.payments.index');
    Route::view('/caretaker/settings', 'caretaker.settings.settings')->name('caretaker.settings');

    // Tenant
    Route::view('/tenant/dashboard', 'tenant.dashboard')->name('tenant.dashboard');
    Route::view('/tenant/unit', 'tenant.unit')->name('tenant.unit');
    Route::view('/tenant/maintenance', 'tenant.maintenance-request')->name('tenant.maintenance');
    Route::view('/tenant/payments', 'tenant.payments')->name('tenant.payments');
    Route::view('/tenant/settings', 'tenant.settings.settings')->name('tenant.settings');
});
