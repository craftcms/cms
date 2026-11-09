<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Session\SessionManager;

/**
 * @since 6.0.0
 */
readonly class StartSessionWithoutPersistence
{
    public function __construct(
        private SessionManager $manager,
        private StartLazySession $startLazySession,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if (! $this->sessionConfigured()) {
            return $next($request);
        }

        // Without a session cookie there's no session to keep from being extended, and a CSRF token
        // returned to the visitor (such as by `users/session-info`) only works if its session is saved.
        // Unsafe requests, such as polling, never persist a session.
        if ($request->isMethodSafe() && $request->cookies->get($this->manager->getSessionConfig()['cookie']) === null) {
            return $this->startLazySession->handle($request, $next);
        }

        $session = $this->manager->driver();
        $session->setId($request->cookies->get($session->getName()));
        $session->setRequestOnHandler($request);
        $session->start();

        $request->setLaravelSession(
            $session
        );

        return $next($request);
    }

    private function sessionConfigured(): bool
    {
        return ($this->manager->getSessionConfig()['driver'] ?? null) !== null;
    }
}
