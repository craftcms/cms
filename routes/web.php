<?php

use CraftCms\Cms\Auth\Enums\CpAuthPath;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Auth\LoginController;
use CraftCms\Cms\Http\Controllers\Auth\TwoFactorAuthenticationController;
use Illuminate\Support\Facades\Route;

if (Cms::config()->loginPath !== false) {
    Route::get(Cms::config()->loginPath, [LoginController::class, 'showLogin']);
    Route::get(CpAuthPath::TwoFactorChallenge->value, [TwoFactorAuthenticationController::class, 'showForm']);
}

Route::middleware('auth:craft')->group(function () {
    if (Cms::config()->logoutPath !== false) {
        Route::get(Cms::config()->logoutPath, [LoginController::class, 'logout']);
    }
});
