<?php

use Illuminate\Support\Facades\Route;

Route::post('plugin-action', fn () => 'action')->name('plugin.action');
