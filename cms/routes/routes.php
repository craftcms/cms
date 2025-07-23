<?php

Route::middleware(['web', 'craft', 'auth', 'craft.cp'])
    ->name('craft.cp.')
    ->prefix(config('craft.general.cpTrigger', 'admin'))
    ->group(__DIR__.'/cp.php');

Route::middleware(['web', 'craft'])
    ->group(__DIR__.'/web.php');
