<?php

use CraftCms\Cms\Http\Controllers\Utilities\UtilitiesController;

Route::get('utilities', [UtilitiesController::class, 'index']);
Route::get('utilities/{id}', [UtilitiesController::class, 'show']);
