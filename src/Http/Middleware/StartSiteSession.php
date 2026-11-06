<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Http\DeferredSessionHandler;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Routing\RouteRegistrar;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Session\SessionManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * Starts the session lazily for requests that arrive without a session cookie.
 *
 * The session is only saved, and its cookie (and `XSRF-TOKEN`) only sent, when
 * something writes to it or puts its CSRF token in the response. That keeps
 * responses for visitors without a session free of cookies, so shared caches
 * can store them. Requests that arrive with a session cookie are handled by
 * {@see StartSession} as usual.
 *
 * Routes opt in with `Route::withLazySession()`, which replaces
 * {@see StartSession} with this middleware.
 *
 * @since 6.0.0
 */
readonly class StartSiteSession
{
    public function __construct(
        private SessionManager $manager,
        private StartSession $startSession,
    ) {}

    /**
     * Registers the `withLazySession()` macro on the router, route registrars, and routes.
     */
    public static function registerRouteMacro(): void
    {
        Router::macro('withLazySession', function (): RouteRegistrar {
            /** @var Router $this */
            return new RouteRegistrar($this)->withLazySession();
        });

        RouteRegistrar::macro('withLazySession', function (): RouteRegistrar {
            /** @var RouteRegistrar $this */
            return $this
                ->middleware([...(array) ($this->attributes['middleware'] ?? []), StartSiteSession::class])
                ->withoutMiddleware(StartSession::class);
        });

        Route::macro('withLazySession', function (): Route {
            /** @var Route $this */
            return $this
                ->middleware(StartSiteSession::class)
                ->withoutMiddleware(StartSession::class);
        });
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $config = $this->manager->getSessionConfig();

        if (($config['driver'] ?? null) === null || $request->cookies->get($config['cookie']) !== null) {
            return $this->startSession->handle($request, $next);
        }

        $session = $this->manager->driver();
        $session->setRequestOnHandler($request);
        $handler = $session->getHandler();
        $deferredHandler = new DeferredSessionHandler($handler);
        $session->setHandler($deferredHandler);
        $used = false;

        try {
            $response = $this->startSession->handle($request, function (Request $request) use ($next, $session, &$used) {
                $id = $session->getId();
                $attributes = $this->attributes($session->all());

                $response = $next($request);

                $used = $session->getId() !== $id
                    || $this->attributes($session->all()) !== $attributes
                    || $this->hasToken($response, $session->token());

                return $response;
            });
        } finally {
            $session->setHandler($handler);
        }

        if ($used) {
            $deferredHandler->flush();
        } elseif ($response instanceof Response) {
            // `StartSession` and `PreventRequestForgery` add these to every response.
            $response->headers->removeCookie($config['cookie'], $config['path'], $config['domain']);
            $response->headers->removeCookie('XSRF-TOKEN', $config['path'], $config['domain']);
        }

        return $response;
    }

    /**
     * Returns the session's attributes, minus bookkeeping that doesn't need a session of its own.
     *
     * `_database_modified_at` is set by {@see PreventStaleDatabaseReads} whenever the request changes
     * database records, so later requests read their own writes. That alone doesn't need a session.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function attributes(array $attributes): array
    {
        unset($attributes['_database_modified_at']);

        return $attributes;
    }

    /**
     * Whether the session's CSRF token was put in the response body or headers, which only works if the session is saved.
     */
    private function hasToken(mixed $response, mixed $token): bool
    {
        if (! $response instanceof Response || ! is_string($token)) {
            return false;
        }

        $content = $response->getContent();

        if (is_string($content) && str_contains($content, $token)) {
            return true;
        }

        // Cookies are skipped, as `PreventRequestForgery` puts the token in `XSRF-TOKEN` on every response.
        foreach ($response->headers->allPreserveCaseWithoutCookies() as $values) {
            foreach ($values as $value) {
                if (is_string($value) && str_contains($value, $token)) {
                    return true;
                }
            }
        }

        return false;
    }
}
