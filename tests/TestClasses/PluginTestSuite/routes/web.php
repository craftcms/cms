<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/bootstrap-plugin', fn () => ['plugin' => 'bootstrap-plugin']);
