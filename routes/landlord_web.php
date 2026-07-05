<?php

use Illuminate\Support\Facades\Route;

/*
| Landlord Web Routes — Developer A domain.
| Merge-safe partial, loaded separately from bootstrap/app.php.
*/

Route::middleware('auth')->group(function () {
    Route::view('/landlord/dashboard', 'landlord.dashboard')->name('landlord.dashboard');
    Route::view('/landlord/properties', 'landlord.properties.properties')->name('landlord.properties.index');
    Route::view('/landlord/properties/create', 'landlord.properties.create')->name('landlord.properties.create');
    Route::view('/landlord/units', 'landlord.units')->name('landlord.units.index');
    Route::view('/landlord/tenants', 'landlord.tenants')->name('landlord.tenants.index');
    Route::view('/landlord/caretakers', 'landlord.caretakers')->name('landlord.caretakers');
    Route::view('/landlord/payments', 'landlord.payments')->name('landlord.payments.index');
    Route::view('/landlord/maintenance', 'landlord.maintenance')->name('landlord.maintenance.index');
    Route::view('/landlord/settings', 'landlord.settings.settings')->name('landlord.settings');
});
