<?php

use CraftCms\Cms\Auth\Enums\CpAuthPath;
use CraftCms\Cms\Auth\LoginRateLimiter;
use CraftCms\Cms\Auth\OAuth\OAuth;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Controllers\Auth\LoginController;
use CraftCms\Cms\Http\Controllers\Auth\OAuthController;
use CraftCms\Cms\Http\Controllers\Auth\SetPasswordController;
use CraftCms\Cms\Http\Controllers\Auth\TwoFactorAuthenticationController;
use CraftCms\Cms\Http\Controllers\Auth\VerifyEmailController;
use CraftCms\Cms\Http\Controllers\SiteRouteController;
use CraftCms\Cms\Http\Middleware\RequireEdition;
use CraftCms\Cms\Route\Routes as CraftRoutes;
use CraftCms\Cms\Site\Sites;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

$routes = app(CraftRoutes::class);

if (Edition::get()->registersFrontendUserRoutes()) {
    if (Cms::config()->getLoginPath() !== false) {
        Route::allowDuringMaintenance()->get(CpAuthPath::TwoFactorChallenge->value, [TwoFactorAuthenticationController::class, 'showForm']);
    }

    /*
     * These paths can be localized per site, so a route is registered for every
     * site's value. Changing them or the sites requires re-running `route:cache`
     * on installs that cache routes.
     */
    if (Cms::isInstalled()) {
        foreach ($routes->localizedConfigPaths('getLoginPath') as $path) {
            Route::allowDuringMaintenance()->get($path, [LoginController::class, 'showLogin']);
            Route::allowDuringMaintenance()->post($path, [LoginController::class, 'attemptLogin'])
                ->middleware('throttle:'.LoginRateLimiter::NAME);
        }

        foreach ($routes->localizedConfigPaths('getVerifyEmailPath') as $path) {
            Route::allowDuringMaintenance()->get($path, [VerifyEmailController::class, 'show']);
            Route::allowDuringMaintenance()->post($path, [VerifyEmailController::class, 'store']);
        }

        foreach ($routes->localizedConfigPaths('getSetPasswordPath') as $path) {
            Route::allowDuringMaintenance()->get($path, [SetPasswordController::class, 'show']);
            Route::allowDuringMaintenance()->post($path, [SetPasswordController::class, 'store']);
        }

        foreach ($routes->localizedConfigPaths('getLogoutPath') as $path) {
            Route::allowDuringMaintenance()->any($path, [LoginController::class, 'logout']);
        }
    }
}

if (OAuth::isAvailable()) {
    Route::allowDuringMaintenance()->middleware([RequireEdition::class.':'.Edition::Pro->value])->group(function () use ($routes) {
        Route::get('oauth/{provider}/redirect', [OAuthController::class, 'redirect'])->name('oauth.redirect');
        Route::get('oauth/{provider}/callback', [OAuthController::class, 'callback'])->name('oauth.callback');

        Route::prefix($routes->cpTriggerRoutePrefix())->middleware('craft.cp')->group(function () {
            Route::get('oauth/{provider}/redirect', [OAuthController::class, 'redirect']);
        });
    });
}

if (! is_null(Cms::config()->setPasswordRequestPath)) {
    Route::allowDuringMaintenance()->get('.well-known/change-password', function (Sites $sites) {
        $uri = Cms::config()->getSetPasswordRequestPath($sites->getCurrentSite()->handle);

        abort_if(is_null($uri), 404);

        return redirect($uri);
    });
}

// Signals support for passkeys without leaking the CP URL, per https://www.w3.org/TR/passkey-endpoints/.
Route::get('.well-known/passkey-endpoints', fn () => new JsonResponse((object) []));

// Route::fallback() only registers GET/HEAD by default
Route::any('{fallbackPlaceholder}', SiteRouteController::class)
    ->where('fallbackPlaceholder', '.*')
    ->fallback()
    ->name('siteFallback');
