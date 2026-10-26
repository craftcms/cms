<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Cms;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as LaravelMiddleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Routing\RouteRegistrar;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Gate;
use Override;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * @since 6.0.0
 */
class PreventRequestsDuringMaintenance extends LaravelMiddleware
{
    private const string ALLOW_DURING_MAINTENANCE_METADATA = 'craft.allowDuringMaintenance';

    /** @var array<int, string> */
    private static array $routeExceptions = [];

    public function __construct(
        Application $app,
        private readonly Router $router,
    ) {
        parent::__construct($app);
    }

    /**
     * @param  Request  $request
     */
    #[Override]
    public function handle($request, Closure $next): mixed
    {
        if (! $this->app->maintenanceMode()->active()) {
            return $next($request);
        }

        $route = $request->route();

        if (! $route instanceof Route) {
            $route = $this->matchRoute($request);

            if ($route === null) {
                return parent::handle($request, $next);
            }

            // Craft routes are checked again once they're resolved, by the `craft` middleware group
            if ($this->isCraftRoute($route)) {
                return $next($request);
            }
        }

        // Routes outside the `craft` group that allow maintenance decide who they let in themselves
        if ($route->getMetadata(self::ALLOW_DURING_MAINTENANCE_METADATA) === true) {
            return $next($request);
        }

        if (! $this->isCraftRoute($route)) {
            return parent::handle($request, $next);
        }

        if (
            $request->getHadToken()
            || Context::getHidden(ResolveSite::HAD_SITE_TOKEN_KEY) === true
            || Gate::check($request->isCpRequest()
                ? 'accessCpWhenSystemIsOff'
                : 'accessSiteWhenSystemIsOff')
        ) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }

    public static function registerRouteMacro(): void
    {
        $metadata = self::ALLOW_DURING_MAINTENANCE_METADATA;

        Router::macro('allowDuringMaintenance', function () use ($metadata): RouteRegistrar {
            /** @var Router $this */
            return new RouteRegistrar($this)->metadata([
                $metadata => true,
            ]);
        });
    }

    /**
     * @param  iterable<Route>  $routes
     * @return list<string>
     */
    public static function routeExceptionTemplates(iterable $routes): array
    {
        return collect($routes)
            ->filter(fn (Route $route) => $route->getMetadata(self::ALLOW_DURING_MAINTENANCE_METADATA) === true)
            ->map(fn (Route $route) => $route->uri())
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  list<string>|null  $templates
     */
    public static function registerRouteExceptions(?array $templates = null): void
    {
        self::$routeExceptions = collect($templates ?? self::routeExceptionTemplates(app(Router::class)->getRoutes()->getRoutes()))
            ->map(fn (string $template) => self::exceptionPath($template))
            ->unique()
            ->values()
            ->all();

        self::except(self::$routeExceptions);
    }

    #[Override]
    protected function inExceptArray($request): bool
    {
        foreach (array_diff($this->getExcludedPaths(), self::$routeExceptions) as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if ($request->fullUrlIs($except) || $request->is($except)) {
                return true;
            }
        }

        return false;
    }

    private function matchRoute(Request $request): ?Route
    {
        try {
            return $this->router->getRoutes()->match($request);
        } catch (HttpExceptionInterface) {
            return null;
        }
    }

    private function isCraftRoute(Route $route): bool
    {
        return in_array('craft', $route->middleware(), true);
    }

    private static function exceptionPath(string $template): string
    {
        $path = str_replace(
            ['{cpTrigger}', '{actionTrigger}'],
            [trim((string) Cms::config()->cpTrigger, '/'), trim(Cms::config()->actionTrigger, '/')],
            $template,
        );

        return preg_replace('/\{[^}]+}/', '*', $path) ?? $path;
    }
}
