<?php

use CraftCms\Cms\Route\Routes as CraftRoutes;
use Illuminate\Support\Facades\Route;

$routes = app(CraftRoutes::class);

Route::middleware(['web', 'craft'])
    ->name('craft.actions.')
    ->group(__DIR__.'/actions.php');

Route::middleware(['web', 'craft', 'craft.cp'])
    ->name('craft.cp.')
    ->prefix($routes->cpTriggerRoutePrefix())
    ->group(__DIR__.'/cp.php');

Route::middleware(['web', 'craft', 'craft.web'])
    ->name('craft.')
    ->group(__DIR__.'/web.php');
