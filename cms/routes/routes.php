<?php

use CraftCms\Cms\Config\GeneralConfig;

Route::middleware(['web', 'craft'])
    ->name('craft.actions.')
    ->group(__DIR__.'/actions.php');

Route::middleware(['web', 'craft', 'craft.cp'])
    ->name('craft.cp.')
    ->prefix(app(GeneralConfig::class)->cpTrigger)
    ->group(__DIR__.'/cp.php');

Route::middleware(['web', 'craft'])
    ->group(__DIR__.'/web.php');
