<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Http\BufferedSessionHandler;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Routing\RouteRegistrar;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Session\SessionManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
 * {@see StartSession} with this middleware. If the route's middleware includes a
 * subclass of {@see StartSession}, such as one in the host app's `web` group, that
 * subclass handles the request instead of Laravel's.
 *
 * This middleware runs at {@see StartSession}'s position in the middleware priority
 * list, not at its position in the `web` group. Middleware that reads the session
 * must be in the priority list after {@see StartSession}, such as with
 * `$middleware->priority()` or `addToMiddlewarePriorityAfter(StartSession::class, …)`.
 *
 * @since 6.0.0
 */
readonly class StartLazySession
{
    public function __construct(
        private SessionManager $manager,
        private StartSession $startSession,
        private Router $router,
        private Container $container,
    ) {}

    /**
     * The route metadata key set by `withLazySession()`.
     */
    public const string METADATA = 'craft.lazySession';

    /**
     * Registers the `withLazySession()` macro on the router, route registrars, and routes.
     *
     * The macro tags routes with metadata rather than adding middleware, so a later
     * `middleware()` call on the same registrar can't drop it. {@see applyTo()} adds this
     * middleware to tagged routes once one is matched.
     */
    public static function registerRouteMacro(): void
    {
        $metadata = self::METADATA;

        Router::macro('withLazySession', function () use ($metadata): RouteRegistrar {
            /** @var Router $this */
            return new RouteRegistrar($this)
                ->metadata([$metadata => true])
                ->withoutMiddleware(StartSession::class);
        });

        RouteRegistrar::macro('withLazySession', function () use ($metadata): RouteRegistrar {
            /** @var RouteRegistrar $this */
            return $this
                ->metadata([$metadata => true])
                ->withoutMiddleware(StartSession::class);
        });

        Route::macro('withLazySession', function () use ($metadata): Route {
            /** @var Route $this */
            return $this
                ->metadata([$metadata => true])
                ->withoutMiddleware(StartSession::class);
        });
    }

    /**
     * Adds this middleware to a matched route if it was tagged by `withLazySession()`.
     */
    public static function applyTo(Route $route): void
    {
        if (self::isAppliedTo($route)) {
            $route->middleware(self::class);
        }
    }

    /**
     * Whether the route was tagged by `withLazySession()`.
     */
    public static function isAppliedTo(?Route $route): bool
    {
        return $route?->getMetadata(self::METADATA) === true;
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $config = $this->manager->getSessionConfig();

        [$startSession, $parameters] = $this->startSession($request);

        if (($config['driver'] ?? null) === null || $request->cookies->get($config['cookie']) !== null) {
            return $startSession->handle($request, $next, ...$parameters);
        }

        $session = $this->manager->driver();
        $session->setRequestOnHandler($request);
        $handler = $session->getHandler();
        $bufferedHandler = new BufferedSessionHandler($handler);
        $session->setHandler($bufferedHandler);
        $used = false;

        try {
            $response = $startSession->handle($request, function (Request $request) use ($next, $session, &$used) {
                $id = $session->getId();
                $attributes = $this->attributes($request, $session->all());

                $response = $next($request);

                $used = $session->getId() !== $id
                    || $this->attributes($request, $session->all()) !== $attributes;

                return $response;
            }, ...$parameters);

            // Checked on the final response, in case a host `StartSession` subclass adds the token to it.
            $used = $used || $this->hasToken($response, $session->token());
        } finally {
            $session->setHandler($handler);
        }

        if ($used) {
            $bufferedHandler->flush();
        } elseif ($response instanceof Response) {
            // `StartSession` and `PreventRequestForgery` add these to every response.
            $response->headers->removeCookie($config['cookie'], $config['path'], $config['domain']);
            $response->headers->removeCookie('XSRF-TOKEN', $config['path'], $config['domain']);
        }

        return $response;
    }

    /**
     * Returns the route's own {@see StartSession} subclass, if it has one, as `withLazySession()` excludes it too,
     * along with its middleware parameters.
     *
     * @return array{StartSession, list<string>}
     */
    private function startSession(Request $request): array
    {
        $route = $request->route();

        if ($route instanceof Route) {
            foreach ($this->router->resolveMiddleware($route->gatherMiddleware()) as $middleware) {
                if (! is_string($middleware)) {
                    continue;
                }

                [$class, $parameters] = array_pad(explode(':', $middleware, 2), 2, null);

                if (is_subclass_of($class, StartSession::class)) {
                    return [$this->container->make($class), $parameters === null ? [] : explode(',', $parameters)];
                }
            }
        }

        return [$this->startSession, []];
    }

    /**
     * Returns the session's attributes, minus bookkeeping that doesn't need a session of its own.
     *
     * `_database_modified_at` is set by {@see PreventStaleDatabaseReads} whenever the request changes
     * database records, so later requests read their own writes. Safe requests can set it too, such as
     * when a page writes caches, so it only counts as using the session on unsafe requests.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function attributes(Request $request, array $attributes): array
    {
        if ($request->isMethodSafe()) {
            unset($attributes['_database_modified_at']);
        }

        return $attributes;
    }

    /**
     * Whether the session's CSRF token was put in the response body or headers, which only works if the session is saved.
     * Streamed responses are assumed to contain it, as their bodies can't be checked.
     */
    private function hasToken(mixed $response, mixed $token): bool
    {
        if (! $response instanceof Response || ! is_string($token)) {
            return false;
        }

        // Streamed bodies can't be checked, so they're assumed to contain it.
        if ($response instanceof StreamedResponse) {
            return true;
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
