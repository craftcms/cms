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

class PreventRequestsDuringMaintenance extends LaravelMiddleware
{
    private const string ALLOW_DURING_MAINTENANCE_METADATA = 'craft.allowDuringMaintenance';

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
            return $this->matchesCraftRoute($request)
                ? $next($request)
                : parent::handle($request, $next);
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

    public static function registerRouteExceptions(): void
    {
        $exceptions = collect(app(Router::class)->getRoutes()->getRoutes())
            ->filter(fn (Route $route) => $route->getMetadata(self::ALLOW_DURING_MAINTENANCE_METADATA) === true)
            ->map(fn (Route $route) => self::exceptionPath($route))
            ->unique()
            ->values()
            ->all();

        self::except($exceptions);
    }

    private function matchesCraftRoute(Request $request): bool
    {
        try {
            return $this->isCraftRoute($this->router->getRoutes()->match($request));
        } catch (HttpExceptionInterface) {
            return false;
        }
    }

    private function isCraftRoute(Route $route): bool
    {
        return in_array('craft', $route->middleware(), true);
    }

    private static function exceptionPath(Route $route): string
    {
        $path = str_replace(
            ['{cpTrigger}', '{actionTrigger}'],
            [trim((string) Cms::config()->cpTrigger, '/'), trim(Cms::config()->actionTrigger, '/')],
            $route->uri(),
        );

        return preg_replace('/\{[^}]+}/', '*', $path) ?? $path;
    }
}
