<?php

use CraftCms\Cms\Http\Controllers\Dashboard\DashboardController;
use CraftCms\Cms\Http\Controllers\Utilities\UtilitiesController;

Route::get('dashboard', DashboardController::class);

Route::get('utilities', [UtilitiesController::class, 'index']);
Route::get('utilities/{id}', [UtilitiesController::class, 'show']);
