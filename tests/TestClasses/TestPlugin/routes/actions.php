<?php

use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\Controllers\HasRoutesActionController;
use Illuminate\Support\Facades\Route;

Route::post('plugin-action', HasRoutesActionController::class)->name('plugin.action');
