<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use CraftCms\Cms\Auth\AuthMethods;
use CraftCms\Cms\Auth\Impersonation;
use CraftCms\Cms\Auth\SessionAuth;
use CraftCms\Cms\Cms;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;
use Override;

class AuthenticateCraftSession extends AuthenticateSession
{
    #[Override]
    /** @param Request $request */
    protected function logout(mixed $request): never
    {
        if (Cms::config()->authGuard === null) {
            parent::logout($request);
        }

        $guard = Cms::config()->getAuthGuard();
        $this->guard()->logoutCurrentDevice();

        $sessionKeys = [
            'password_hash_'.$guard,
            Cms::config()->getPasswordConfirmationKey(),
        ];

        if (SessionAuth::$authAccessParam !== null) {
            $sessionKeys[] = SessionAuth::$authAccessParam;
        }

        $request->session()->forget($sessionKeys);
        app(AuthMethods::class)->setUser(null);
        app(Impersonation::class)->setImpersonatorId(null);

        throw new AuthenticationException(
            'Unauthenticated.',
            [$guard],
            $this->redirectTo($request),
        );
    }
}
