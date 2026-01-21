<?php

use CraftCms\Cms\Cms;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'craft'])
    ->name('craft.actions.')
    ->group(__DIR__.'/actions.php');

Route::middleware(['web', 'craft', 'craft.cp'])
    ->name('craft.cp.')
    ->prefix(Cms::config()->cpTrigger)
    ->group(__DIR__.'/cp.php');

Route::middleware(['web', 'craft', 'craft.web'])
    ->name('craft.')
    ->group(__DIR__.'/web.php');
