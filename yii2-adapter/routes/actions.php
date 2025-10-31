<?php

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Dashboard\WidgetsController;

/**
 * Actions that are accessible without CP can be registered here.
 */
Route::prefix(Cms::config()->actionTrigger)->group(function() {
    // Nothing for now
});

/**
 * CP Actions, if actions need to be accessible both in /{cpTrigger} and
 * the frontend site, you need to register them above as well.
 */
Route::prefix(implode('/', [
    Cms::config()->cpTrigger,
    Cms::config()->actionTrigger,
]))->middleware(['craft.cp'])->group(function() {
    /**
     * Actions not needing auth
     */

    // Nothing for now

    /**
     * Actions needing auth
     */
    Route::middleware(['auth'])->group(function() {
        // Widgets
        Route::post('dashboard/create-widget', [WidgetsController::class, 'store']);
        Route::post('dashboard/save-widget-settings', [WidgetsController::class, 'update']);
        Route::post('dashboard/delete-user-widget', [WidgetsController::class, 'delete']);
        Route::post('dashboard/change-widget-colspan', [WidgetsController::class, 'updateColspan']);
        Route::post('dashboard/reorder-user-widgets', [WidgetsController::class, 'reorder']);
    });
});
