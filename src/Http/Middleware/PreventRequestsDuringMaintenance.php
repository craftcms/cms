<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as LaravelMiddleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Override;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class PreventRequestsDuringMaintenance extends LaravelMiddleware
{
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

        if (! $route instanceof Route && $this->matchesCraftRoute($request)) {
            return $next($request);
        }

        if ($route instanceof Route && $request->isCpRequest()) {
            return $next($request);
        }

        if (
            $request->getHadToken() ||
            filled($request->siteToken()) ||
            Gate::check('accessSiteWhenSystemIsOff')
        ) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }

    private function matchesCraftRoute(Request $request): bool
    {
        try {
            $route = $this->router->getRoutes()->match($request);
        } catch (HttpExceptionInterface) {
            return false;
        }

        return in_array('craft', $route->gatherMiddleware(), true);
    }
}
