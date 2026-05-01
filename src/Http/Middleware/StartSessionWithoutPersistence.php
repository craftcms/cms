<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Session\SessionManager;

readonly class StartSessionWithoutPersistence
{
    public function __construct(
        private SessionManager $manager,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if (! $this->sessionConfigured()) {
            return $next($request);
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
