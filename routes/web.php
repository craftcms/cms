<?php

use CraftCms\Cms\Auth\Enums\CpAuthPath;
use CraftCms\Cms\Auth\OAuth\OAuth;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Controllers\Auth\LoginController;
use CraftCms\Cms\Http\Controllers\Auth\OAuthController;
use CraftCms\Cms\Http\Controllers\Auth\TwoFactorAuthenticationController;
use CraftCms\Cms\Http\Controllers\Auth\VerifyEmailController;
use CraftCms\Cms\Http\Middleware\RequireEdition;
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

if (OAuth::isAvailable()) {
    Route::middleware([RequireEdition::class.':'.Edition::Pro->value])->group(function () {
        Route::get('oauth/{provider}/redirect', [OAuthController::class, 'redirect'])->name('oauth.redirect');
        Route::get('oauth/{provider}/callback', [OAuthController::class, 'callback'])->name('oauth.callback');

        Route::prefix(Cms::config()->cpTrigger)->middleware('craft.cp')->group(function () {
            Route::get('oauth/{provider}/redirect', [OAuthController::class, 'redirect']);
        });
    });
}

if (! is_null(Cms::config()->setPasswordRequestPath)) {
    Route::get('.well-known/change-password', function (Sites $sites) {
        $uri = Cms::config()->getSetPasswordRequestPath($sites->getCurrentSite()->handle);

        abort_if(is_null($uri), 404);

        return redirect($uri);
    });
}
