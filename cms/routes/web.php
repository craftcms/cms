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

        Route::prefix(config('craft.general.cpTrigger', 'admin'))
            ->middleware([
                'auth',
                'craft.cp',
            ])
            ->group(function () {
                // Add new control panel routes here
                Route::get('utilities', [UtilitiesController::class, 'index']);
                Route::get('utilities/{id}', [UtilitiesController::class, 'show']);

                Route::prefix(config('craft.general.actionTrigger', 'actions'))->group(function () {
                    Route::prefix('utilities')->group(function () {
                        // DeprecationErrors
                        Route::post('get-deprecation-error-traces-modal', [DeprecationErrorsController::class, 'getDeprecationErrorTracesModal']);
                        Route::post('delete-deprecation-error', [DeprecationErrorsController::class, 'deleteDeprecationError']);
                        Route::post('delete-all-deprecation-errors', [DeprecationErrorsController::class, 'deleteAllDeprecationErrors']);

                        // ClearCaches
                        Route::post('clear-caches-perform-action', [ClearCachesController::class, 'clearCaches']);
                        Route::post('invalidate-tags', [ClearCachesController::class, 'invalidateTags']);

                        // DbBackup
                        Route::post('db-backup-perform-action', DbBackupController::class);

                        // FindAndReplace
                        Route::post('find-and-replace-perform-action', FindAndReplaceController::class);

                        // Migrations
                        Route::post('apply-new-migrations', MigrationsController::class);
                    });
                });

            });
    });
