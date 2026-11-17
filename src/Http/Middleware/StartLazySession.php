<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Http\BufferedSessionHandler;
use CraftCms\Cms\Http\Events\LazySessionSaved;
use CraftCms\Cms\Support\Facades\ResponseHeaders;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Cookie\QueueingFactory;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Routing\RouteRegistrar;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Cookie;
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
 * Responses that save or refresh the session get no-cache headers, through
 * {@see \CraftCms\Cms\Http\ResponseHeaders::noCache()}. When a request without a
 * session cookie saves one, {@see LazySessionSaved} reports why.
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
        private QueueingFactory $cookies,
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

        if (($config['driver'] ?? null) === null) {
            return $startSession->handle($request, $next, ...$parameters);
        }

        if ($request->cookies->get($config['cookie']) !== null) {
            $response = $startSession->handle($request, $next, ...$parameters);
            ResponseHeaders::noCache();

            return $response;
        }

        $session = $this->manager->driver();
        $session->setRequestOnHandler($request);
        $handler = $session->getHandler();
        $bufferedHandler = new BufferedSessionHandler($handler);
        $session->setHandler($bufferedHandler);
        $keys = [];
        $regenerated = false;

        try {
            $response = $startSession->handle($request, function (Request $request) use ($next, $session, &$keys, &$regenerated) {
                $id = $session->getId();
                $attributes = $this->attributes($request, $session->all());

                $response = $next($request);

                $regenerated = $session->getId() !== $id;
                $keys = $this->changedKeys($attributes, $this->attributes($request, $session->all()));

                return $response;
            }, ...$parameters);

            // Checked on the final response, in case a host `StartSession` subclass adds the token to it.
            $token = $this->findToken($response, $session->token());
        } finally {
            $session->setHandler($handler);
        }

        if ($regenerated || $keys !== [] || $token !== null) {
            $bufferedHandler->flush();
            ResponseHeaders::noCache();
            $this->report($request, $response, $keys, $regenerated, $token);
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
     * Returns the keys whose values differ between the two sets of session attributes, sorted.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return list<string>
     */
    private function changedKeys(array $before, array $after): array
    {
        $keys = array_filter(
            array_unique([...array_keys($before), ...array_keys($after)]),
            fn (string|int $key) => ! array_key_exists($key, $before)
                || ! array_key_exists($key, $after)
                || $before[$key] !== $after[$key],
        );
        sort($keys);

        return array_map(strval(...), $keys);
    }

    /**
     * Returns where the session's CSRF token was put in the response, which only works if the session is saved:
     * `body`, `header`, or `cookie`. Streamed responses are assumed to contain it in their body, as it can't be checked.
     */
    private function findToken(mixed $response, mixed $token): ?string
    {
        if (! $response instanceof Response || ! is_string($token)) {
            return null;
        }

        // Streamed bodies can't be checked, so they're assumed to contain it.
        if ($response instanceof StreamedResponse) {
            return 'body';
        }

        $content = $response->getContent();

        if (is_string($content) && str_contains($content, $token)) {
            return 'body';
        }

        foreach ($response->headers->allPreserveCaseWithoutCookies() as $values) {
            foreach ($values as $value) {
                if (is_string($value) && str_contains($value, $token)) {
                    return 'header';
                }
            }
        }

        // Queued cookies are only added to the response after this middleware, by `AddQueuedCookiesToResponse`.
        // `XSRF-TOKEN` is skipped, as `PreventRequestForgery` puts the token in it on every response.
        $inCookie = array_any(
            [...$response->headers->getCookies(), ...$this->cookies->getQueuedCookies()],
            fn (Cookie $cookie) => $cookie->getName() !== 'XSRF-TOKEN' && str_contains((string) $cookie->getValue(), $token),
        );

        return $inCookie ? 'cookie' : null;
    }

    /**
     * Reports why the session was saved, as it keeps shared caches from storing the response.
     *
     * In debug mode, the reason is also sent in an `X-Craft-Session-Saved` header.
     *
     * @param  list<string>  $keys
     */
    private function report(Request $request, mixed $response, array $keys, bool $regenerated, ?string $token): void
    {
        event(new LazySessionSaved($request, $keys, $regenerated, $token));

        $reason = implode('; ', array_filter([
            $keys !== [] ? 'keys='.implode(',', $keys) : null,
            $regenerated ? 'regenerated' : null,
            $token !== null ? "token=$token" : null,
        ]));

        Log::debug("Saved a session for a request without one ($reason).", [
            'url' => $request->fullUrl(),
        ]);

        if ($response instanceof Response && app()->hasDebugModeEnabled()) {
            $response->headers->set('X-Craft-Session-Saved', $reason);
        }
    }
}
