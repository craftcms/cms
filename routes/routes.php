<?php

use CraftCms\Cms\Auth\OAuthServer\Http\Controllers\MetadataController;
use CraftCms\Cms\Http\Middleware\EnsureInstalled;
use CraftCms\Cms\Route\Routes as CraftRoutes;
use Illuminate\Support\Facades\Route;

$routes = app(CraftRoutes::class);

// The key set that verifies OAuth access tokens, for any API that issues them. It's outside the `web` group, so it
// has no session.
Route::get('.well-known/jwks.json', [MetadataController::class, 'keys'])
    ->middleware(EnsureInstalled::class)
    ->name('craft.oauth.jwks');

Route::middleware(['web', 'craft'])
    ->name('craft.actions.')
    ->group(__DIR__.'/actions.php');

Route::middleware(['web', 'craft', 'craft.cp'])
    ->name('craft.cp.')
    ->prefix($routes->cpTriggerRoutePrefix())
    ->group(__DIR__.'/cp.php');

Route::middleware(['web', 'craft', 'craft.web'])
    ->withLazySession()
    ->name('craft.')
    ->group(__DIR__.'/web.php');
