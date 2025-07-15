<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'craft'])
    ->group(function () {
        // Add new routes here

        Route::prefix(config('craft.general.cpTrigger'))
            ->middleware([
                'auth',
                'craft.cp',
            ])
            ->group(function () {
                // Add new control panel routes here
            });
    });
