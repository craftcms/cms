<?php

use Illuminate\Support\Facades\Route;

//Route::get('admin/dashboard', [\Craft\Cms\Http\Controllers\DashboardController::class, 'index'])->name('dashboard.index');

/**
 * Register a fallback route that boots up the Yii-based Craft
 */
Route::any('{any}', function() {
    abort(404);
})
    ->middleware([
        'web',
        \Craft\Cms\Yii\LegacyMiddleware::class,
    ])
    ->withoutMiddleware(
        \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
    )
    ->where('any', '.*')
    ->fallback();
