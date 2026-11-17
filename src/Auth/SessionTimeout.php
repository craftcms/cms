<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth;

use Carbon\CarbonImmutable;
use Illuminate\Auth\SessionGuard;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;

use function CraftCms\Cms\craftAuth;

/**
 * Tracks when a signed-in user's session will expire, so the control panel can
 * warn them beforehand.
 *
 * Laravel expires a session once it has gone `session.lifetime` minutes without
 * being saved, and saves it on every request that starts it. The time of the
 * last such request is recorded in the session, so the expiry can be reported
 * by a request that reads the session without saving it, and so without
 * extending it.
 *
 * @since 6.0.0
 */
#[Singleton]
class SessionTimeout
{
    /** The session key holding the Unix time of the last request that saved the session. */
    public const string LAST_ACTIVITY_KEY = '__craft_last_activity';

    /** The response header carrying the session's expiry, as a Unix time. */
    public const string HEADER = 'X-Craft-Session-Expires';

    /**
     * Records the current request as the session's latest activity.
     */
    public function touch(Session $session): void
    {
        $session->put(self::LAST_ACTIVITY_KEY, CarbonImmutable::now()->getTimestamp());
    }

    /**
     * Returns when the current user's session will expire, or `null` if there's
     * no signed-in user, or if they'd be signed back in by a remember-me cookie
     * once it does.
     */
    public function expiresAt(Request $request): ?CarbonImmutable
    {
        if (! $request->hasSession() || ! craftAuth()->check() || $this->isRemembered($request)) {
            return null;
        }

        $lastActivity = $request->session()->get(self::LAST_ACTIVITY_KEY);

        if (! is_int($lastActivity)) {
            return null;
        }

        return CarbonImmutable::createFromTimestamp($lastActivity)
            ->addMinutes((int) config('session.lifetime', 120));
    }

    private function isRemembered(Request $request): bool
    {
        $guard = craftAuth();

        return $guard instanceof SessionGuard && $request->cookies->has($guard->getRecallerName());
    }
}
