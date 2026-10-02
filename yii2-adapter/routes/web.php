<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Middleware\PreventRequestsDuringMaintenance;
use CraftCms\Cms\Http\Middleware\RequireAdminChanges;
use CraftCms\Cms\Route\Routes as CraftRoutes;
use CraftCms\Yii2Adapter\Http\ExcludeCsrfValidationForLegacyController;
use CraftCms\Yii2Adapter\Http\FallbackTransformController;
use CraftCms\Yii2Adapter\Http\LegacyMiddleware;
use CraftCms\Yii2Adapter\Http\SavePluginSettingsController;
use Illuminate\Support\Facades\Route;

$routes = app(CraftRoutes::class);
$sharedActionRouteGroups = $routes->actionTriggerRoutePrefix() === $routes->cpActionTriggerRoutePrefix()
    ? [[$routes->cpActionTriggerRoutePrefix(), ['web', 'craft.cp']]]
    : [
        [$routes->actionTriggerRoutePrefix(), ['craft.web']],
        [$routes->cpActionTriggerRoutePrefix(), ['web', 'craft.cp']],
    ];

foreach ($sharedActionRouteGroups as [$prefix, $middleware]) {
    Route::middleware(['web', 'craft', ...$middleware])
        ->prefix($prefix)
        ->get('assets/generate-fallback-transform', FallbackTransformController::class);
}

Route::middleware([
    'web',
    'craft',
    'craft.cp',
    'auth',
    'can:accessCp',
    RequireAdminChanges::class,
])
    ->prefix($routes->cpActionTriggerRoutePrefix())
    ->post('plugins/save-plugin-settings', SavePluginSettingsController::class);

foreach ($sharedActionRouteGroups as [$prefix, $middleware]) {
    Route::middleware([
        'craft',
        ExcludeCsrfValidationForLegacyController::class,
        ...$middleware,
        LegacyMiddleware::class,
    ])
        ->name(in_array('craft.cp', $middleware, true) ? 'craft.cp.legacy.action' : 'craft.legacy.action')
        ->prefix($prefix)
        ->any('{any}', fn() => abort(404))
        ->withoutMiddleware(PreventRequestsDuringMaintenance::class)
        ->where('any', '.*');
}

/**
 * Register the remaining legacy CP routes after the CMS package's
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
