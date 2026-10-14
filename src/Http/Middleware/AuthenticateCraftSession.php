<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Auth\AuthMethods;
use CraftCms\Cms\Auth\Impersonation;
use CraftCms\Cms\Auth\SessionAuth;
use CraftCms\Cms\Cms;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;
use Override;

/**
 * @since 6.0.0
 */
class AuthenticateCraftSession extends AuthenticateSession
{
    #[Override]
    public function handle(mixed $request, Closure $next): mixed
    {
        if (! $this->auth->guard() instanceof StatefulGuard) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }

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
