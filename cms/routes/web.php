<?php

use CraftCms\Cms\Http\Controllers\Utilities\ClearCachesController;
use CraftCms\Cms\Http\Controllers\Utilities\DbBackupController;
use CraftCms\Cms\Http\Controllers\Utilities\DeprecationErrorsController;
use CraftCms\Cms\Http\Controllers\Utilities\FindAndReplaceController;
use CraftCms\Cms\Http\Controllers\Utilities\MigrationsController;
use CraftCms\Cms\Http\Controllers\Utilities\UtilitiesController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'craft'])
    ->group(function () {
        // Add new routes here

        /** @todo This config doesn't actually work and always defaults to admin right now */
        Route::prefix(config('craft.general.cpTrigger', 'admin'))
            ->middleware([
                'auth',
                'craft.cp',
            ])
            ->group(function () {
                // Add new control panel routes here
                Route::get('utilities', [UtilitiesController::class, 'index']);
                Route::get('utilities/{id}', [UtilitiesController::class, 'show']);

                /** @todo This config doesn't actually work and always defaults to actions right now */
                Route::prefix(config('craft.general.actionTrigger', 'actions'))->group(function () {
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

            });
    });
