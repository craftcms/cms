<?php

use CraftCms\Cms\Cms;
use CraftCms\Yii2Adapter\Http\LegacyMiddleware;

Route::middleware(['web', 'craft', LegacyMiddleware::class])
    ->name('craft.actions.')
    ->group(__DIR__ . '/actions.php');

Route::middleware(['web', 'craft', 'craft.cp', LegacyMiddleware::class])
    ->name('craft.cp.')
    ->prefix(Cms::config()->cpTrigger)
    ->group(__DIR__ . '/cp.php');

Route::middleware(['web', 'craft', LegacyMiddleware::class])
    ->group(__DIR__ . '/web.php');
