<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('plugin-cp', fn () => 'cp')->name('plugin.cp');
Route::get('plugin-cp-restricted', fn () => 'restricted cp')->middleware('can:testPluginPermission');
