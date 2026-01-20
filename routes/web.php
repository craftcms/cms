<?php

use CraftCms\Cms\Auth\Enums\CpAuthPath;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Controllers\Auth\LoginController;
use CraftCms\Cms\Http\Controllers\Auth\TwoFactorAuthenticationController;
use CraftCms\Cms\Http\Controllers\Auth\VerifyEmailController;
use CraftCms\Cms\Site\Sites;
use Illuminate\Support\Facades\Route;

if (Edition::get()->registersFrontendUserRoutes()) {
    if (Cms::config()->loginPath !== false) {
        Route::get(Cms::config()->loginPath, [LoginController::class, 'showLogin']);
        Route::get(CpAuthPath::TwoFactorChallenge->value, [TwoFactorAuthenticationController::class, 'showForm']);
    }

    if (Cms::config()->verifyEmailPath !== false) {
        Route::get(Cms::config()->verifyEmailPath, [VerifyEmailController::class, 'show']);
        Route::post(Cms::config()->verifyEmailPath, [VerifyEmailController::class, 'store']);
    }

    Route::middleware('auth:craft')->group(function () {
        if (Cms::config()->logoutPath !== false) {
            Route::get(Cms::config()->logoutPath, [LoginController::class, 'logout']);
        }
    });
}

if (! is_null(Cms::config()->setPasswordRequestPath)) {
    Route::get('.well-known/change-password', function (Sites $sites) {
        $uri = Cms::config()->getSetPasswordRequestPath($sites->getCurrentSite()->handle);

        abort_if(is_null($uri), 404);

        return redirect($uri);
    });
}
