<?php

use Illuminate\Support\Facades\Route;

// Route::get('admin/dashboard', [\Craft\Cms\Http\Controllers\DashboardController::class, 'index'])->name('dashboard.index');

Route::middleware([
    'web',
    'craft',
])
    ->group(function () {

        // Add new routes & controllers here

    });
