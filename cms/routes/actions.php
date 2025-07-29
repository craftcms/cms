<?php

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Http\Controllers\Utilities\ClearCachesController;
use CraftCms\Cms\Http\Controllers\Utilities\DbBackupController;
use CraftCms\Cms\Http\Controllers\Utilities\DeprecationErrorsController;
use CraftCms\Cms\Http\Controllers\Utilities\FindAndReplaceController;
use CraftCms\Cms\Http\Controllers\Utilities\MigrationsController;

$generalConfig = app(GeneralConfig::class);

/**
 * Actions that are accessible anonymously can be registered here.
 */
Route::prefix($generalConfig->actionTrigger)->group(function () {});

/**
 * Actions that are accessible through the control panel can be registered here.
 */
Route::prefix(implode('/', [
    $generalConfig->cpTrigger,
    $generalConfig->actionTrigger,
]))->middleware(['auth', 'craft.cp'])->group(function () {
    // DeprecationErrors
    Route::post('utilities/get-deprecation-error-traces-modal', [DeprecationErrorsController::class, 'getDeprecationErrorTracesModal']);
    Route::post('utilities/delete-deprecation-error', [DeprecationErrorsController::class, 'deleteDeprecationError']);
    Route::post('utilities/delete-all-deprecation-errors', [DeprecationErrorsController::class, 'deleteAllDeprecationErrors']);

    // ClearCaches
    Route::post('utilities/clear-caches-perform-action', [ClearCachesController::class, 'clearCaches']);
    Route::post('utilities/invalidate-tags', [ClearCachesController::class, 'invalidateTags']);

    // DbBackup
    Route::post('utilities/db-backup-perform-action', DbBackupController::class);

    // FindAndReplace
    Route::post('utilities/find-and-replace-perform-action', FindAndReplaceController::class);

    // Migrations
    Route::post('utilities/apply-new-migrations', MigrationsController::class);
});
