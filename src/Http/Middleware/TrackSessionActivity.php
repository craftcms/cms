<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Auth\SessionTimeout;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\craftAuth;

/**
 * Records each request that saves a signed-in user's session as its latest
 * activity, and reports the session's resulting expiry in the
 * {@see SessionTimeout::HEADER} response header.
 *
 * Requests that read the session without saving it, such as
 * `users/session-info`, are skipped, so they don't push the expiry back.
 *
 * @since 6.0.0
 */
readonly class TrackSessionActivity
{
    public function __construct(
        private SessionTimeout $timeout,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if (! $this->savesSession($request)) {
            return $next($request);
        }

        // Before the request, so anything it renders reports the new expiry.
        if (craftAuth()->check()) {
            $this->timeout->touch($request->session());
        }

        $response = $next($request);

        // And after, for the request that signs the user in.
        if (craftAuth()->check()) {
            $this->timeout->touch($request->session());

            if ($response instanceof Response && ($expiresAt = $this->timeout->expiresAt($request))) {
                $response->headers->set(SessionTimeout::HEADER, (string) $expiresAt->getTimestamp());
            }
        }

        return $response;
    }

    private function savesSession(Request $request): bool
    {
        return $request->hasSession()
            && ! in_array(StartSession::class, $request->route()?->excludedMiddleware() ?? [], true);
    }
}
