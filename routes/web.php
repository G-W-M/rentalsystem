<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — Developer B domain
|--------------------------------------------------------------------------
| Session-based pages. The Blade layouts POST to route('logout'); the API
| logout (token revoke) lives in routes/api.php for native clients.
| Named dashboard routes exist so the shared layouts' route() calls resolve.
| Developer A appends landlord/property web pages to this same file.
*/

Route::view('/', 'welcome')->name('home');

// ----- Auth pages -----
Route::view('/login', 'auth.login')->name('login');
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
    // Caretaker
    Route::view('/caretaker/dashboard', 'caretaker.dashboard')->name('caretaker.dashboard');
    Route::view('/caretaker/tasks', 'caretaker.tasks')->name('caretaker.tasks');
    Route::view('/caretaker/maintenance', 'caretaker.maintenance')->name('caretaker.maintenance');
    Route::view('/caretaker/payments', 'caretaker.payments')->name('caretaker.payments');

    // Tenant
    Route::view('/tenant/dashboard', 'tenant.dashboard')->name('tenant.dashboard');
    Route::view('/tenant/unit', 'tenant.unit')->name('tenant.unit');
    Route::view('/tenant/maintenance', 'tenant.maintenance-request')->name('tenant.maintenance');
    Route::view('/tenant/payments', 'tenant.payments')->name('tenant.payments');
});