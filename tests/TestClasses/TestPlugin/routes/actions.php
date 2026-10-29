<?php

declare(strict_types=1);

use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\Controllers\HasRoutesActionController;
use Illuminate\Support\Facades\Route;

Route::post('plugin-action', HasRoutesActionController::class)->name('plugin.action');
Route::post('plugin-action-restricted', HasRoutesActionController::class)->middleware('can:testPluginPermission');
