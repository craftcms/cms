<?php

use CraftCms\Yii2Adapter\Http\LegacyMiddleware;
use Illuminate\Support\Facades\Route;

/**
 * Register a fallback route that boots up the Yii-based Craft
 */
Route::any('{any}', function() {
    abort(404);
})
    ->middleware([
        'craft',
        'craft.web',
        LegacyMiddleware::class,
    ])
    ->where('any', '.*')
    ->fallback();
