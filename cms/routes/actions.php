<?php

use CraftCms\Cms\Http\Controllers\Dashboard\Widgets\CraftSupportController;
use CraftCms\Cms\Http\Controllers\Dashboard\Widgets\FeedController;
use CraftCms\Cms\Http\Controllers\Dashboard\WidgetsController;
use CraftCms\Cms\Http\Controllers\Utilities\ClearCachesController;
use CraftCms\Cms\Http\Controllers\Utilities\DbBackupController;
use CraftCms\Cms\Http\Controllers\Utilities\DeprecationErrorsController;
use CraftCms\Cms\Http\Controllers\Utilities\FindAndReplaceController;
use CraftCms\Cms\Http\Controllers\Utilities\MigrationsController;

/**
 * Actions that are accessible anonymously can be registered here.
 */
Route::prefix(config('craft.general.actionTrigger', 'actions'))->group(function () {});

/**
 * Actions that are accessible through the control panel can be registered here.
 */
Route::prefix(implode('/', [
    config('craft.general.cpTrigger', 'admin'),
    config('craft.general.actionTrigger', 'actions'),
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

    // Widgets
    Route::post('dashboard/create-widget', [WidgetsController::class, 'store']);
    Route::post('dashboard/save-widget-settings', [WidgetsController::class, 'update']);
    Route::post('dashboard/delete-user-widget', [WidgetsController::class, 'delete']);
    Route::post('dashboard/change-widget-colspan', [WidgetsController::class, 'updateColspan']);
    Route::post('dashboard/reorder-user-widgets', [WidgetsController::class, 'reorder']);
    Route::post('dashboard/cache-feed-data', [FeedController::class, 'cacheData']);
    Route::post('dashboard/send-support-request', CraftSupportController::class);
});
