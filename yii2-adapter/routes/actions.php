<?php

/**
 * Register a fallback route that boots up the Yii-based Craft
 */

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Dashboard\WidgetsController;

Route::prefix(implode('/', [
    Cms::config()->cpTrigger,
    Cms::config()->actionTrigger,
]))->middleware([
    'web',
    'craft',
    'craft.cp',
    \CraftCms\Yii2Adapter\Http\LegacyMiddleware::class,
])->group(function() {
    Route::middleware(['auth'])->group(function() {
        // Widgets
        Route::post('dashboard/create-widget', [WidgetsController::class, 'store']);
        Route::post('dashboard/save-widget-settings', [WidgetsController::class, 'update']);
        Route::post('dashboard/delete-user-widget', [WidgetsController::class, 'delete']);
        Route::post('dashboard/change-widget-colspan', [WidgetsController::class, 'updateColspan']);
        Route::post('dashboard/reorder-user-widgets', [WidgetsController::class, 'reorder']);
    });
});
