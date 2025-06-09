<?php

use Illuminate\Support\Facades\Route;

//Route::get('admin/dashboard', [\Craft\Cms\Http\Controllers\DashboardController::class, 'index'])->name('dashboard.index');

Route::middleware([
    'web',
    'craft',
])
/** TODO: Make Craft+Laravel CSRF token work */
->withoutMiddleware(
    \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
)
->group(function () {

    // Add new routes & controllers here

    /**
     * Register a fallback route that boots up the Yii-based Craft
     */
    Route::any('{any}', function() {
        abort(404);
    })
        ->middleware(\Craft\Cms\Yii\LegacyMiddleware::class)
        ->where('any', '.*')
        ->fallback();
});
