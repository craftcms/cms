<?php

use CraftCms\Cms\Cms;
use CraftCms\Yii2Adapter\Http\ExcludeCsrfValidationForLegacyController;
use CraftCms\Yii2Adapter\Http\LegacyMiddleware;
use Illuminate\Support\Facades\Route;

/**
 * Register catch-all routes that boot up the Yii-based Craft for any
 * request the CMS package didn't match. These are intentionally not
 * fallback() routes: Laravel keeps the first matching fallback, so the
 * CMS package's own Route::fallback(abort(404)) (registered during
 * RouteServiceProvider::boot()) would otherwise win. As regular routes
 * registered after the CMS routes via Yii2ServiceProvider's booted()
 * callback, they're tried after all CMS specific routes but before any
 * fallback — so unmatched paths reach the legacy Yii application, where
 * UrlManager::EVENT_REGISTER_CP_URL_RULES / EVENT_REGISTER_SITE_URL_RULES
 * rules are honored.
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
    ->any('{any}', fn() => abort(404))
    ->where('any', '.*');
