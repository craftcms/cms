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
        //\Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        //\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
        \Craft\Cms\Yii\LegacyMiddleware::class,
    ])
    ->where('any', '.*')
    ->fallback();
