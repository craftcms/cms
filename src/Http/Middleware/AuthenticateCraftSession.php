<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Auth\AuthMethods;
use CraftCms\Cms\Auth\Impersonation;
use CraftCms\Cms\Auth\SessionAuth;
use CraftCms\Cms\Cms;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Elements\User;
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

        // Sign out users who were deactivated, suspended or made pending since they signed in.
        // Locked users stay signed in, and an impersonated user can have any status.
        $user = $request->hasSession() ? $request->user() : null;

        if (
            $user instanceof CraftUser &&
            $user->asElement()->getStatus() !== User::STATUS_ACTIVE &&
            ! app(Impersonation::class)->isImpersonating()
        ) {
            if ($request->isCpRequest()) {
                $this->logout($request);
            }

            // Site requests carry on as a guest, so public pages still load
            $this->signOut($request);
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
        $this->signOut($request);

        throw new AuthenticationException(
            'Unauthenticated.',
            [$guard],
            $this->redirectTo($request),
        );
    }

    /**
     * Signs the user out of Craft's guard and forgets Craft's session keys, leaving the rest of the session alone.
     */
    private function signOut(Request $request): void
    {
        $this->guard()->logoutCurrentDevice();

        $sessionKeys = [
            'password_hash_'.Cms::config()->getAuthGuard(),
            Cms::config()->getPasswordConfirmationKey(),
        ];

        if (SessionAuth::$authAccessParam !== null) {
            $sessionKeys[] = SessionAuth::$authAccessParam;
        }

        $request->session()->forget($sessionKeys);
        app(AuthMethods::class)->setUser(null);
        app(Impersonation::class)->setImpersonatorId(null);
    }
}
