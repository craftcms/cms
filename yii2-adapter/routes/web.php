<?php

use CraftCms\Cms\Cms;
use CraftCms\Cms\Route\Routes as CraftRoutes;
use CraftCms\Yii2Adapter\Http\ExcludeCsrfValidationForLegacyController;
use CraftCms\Yii2Adapter\Http\LegacyMiddleware;
use Illuminate\Support\Facades\Route;

$routes = app(CraftRoutes::class);

/**
 * Register the remaining legacy CP and action routes after the CMS package's
 * fixed routes. Site URL rules are resolved by the adapter's site fallback
 * middleware.
 *
 * The CP catch-all is registered inside the `craft.cp` middleware group
 * with a `craft.cp.*` route name so HandleTemplateRequest will fall back
 * to rendering CP templates (e.g. plugin nav links pointing at paths the
 * plugin only ships templates for).
 */
Route::middleware([
    'web',
    'craft',
    'craft.cp',
    'auth',
    'can:accessCp',
    ExcludeCsrfValidationForLegacyController::class,
    LegacyMiddleware::class,
])
    ->name('craft.cp.legacy')
    ->prefix(Cms::config()->cpTrigger)
    ->any('{any}', fn() => abort(404))
    ->where('any', '.*');

Route::middleware(['craft', ExcludeCsrfValidationForLegacyController::class, 'craft.web', LegacyMiddleware::class])
    ->name('craft.legacy.action')
    ->prefix($routes->actionTriggerRoutePrefix())
    ->any('{any}', fn() => abort(404))
    ->where('any', '.*');
