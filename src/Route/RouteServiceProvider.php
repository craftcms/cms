<?php

declare(strict_types=1);

namespace CraftCms\Cms\Route;

use CraftCms\Cms\Http\Middleware\CheckForUpdates;
use CraftCms\Cms\Http\Middleware\CheckRequirements;
use CraftCms\Cms\Http\Middleware\CheckSchemaVersion;
use CraftCms\Cms\Http\Middleware\ExtractNamespace;
use CraftCms\Cms\Http\Middleware\FlushProjectConfig;
use CraftCms\Cms\Http\Middleware\HandleActionRequest;
use CraftCms\Cms\Http\Middleware\HandleInertiaRequests;
use CraftCms\Cms\Http\Middleware\HandleTokenRequest;
use CraftCms\Cms\Http\Middleware\RequireCpRequest;
use CraftCms\Cms\Http\Middleware\SendPoweredByHeader;
use CraftCms\Cms\Http\Middleware\SetCraftGuard;
use CraftCms\Cms\Http\Middleware\UpdateLocale;
use CraftCms\Cms\Route\Data\Route;
use CraftCms\Cms\Shared\Models\Info;
use CraftCms\Cms\Site\Events\SiteDeleted;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Override;

final class RouteServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        /**
         * These middleware are special and need to
         * run before any other middleware as they
         * rewrite the request
         */
        $kernel = $this->app->get(HttpKernel::class);
        $kernel->setGlobalMiddleware(array_merge([
            ExtractNamespace::class,
            HandleTokenRequest::class,
            HandleActionRequest::class,
        ], $kernel->getGlobalMiddleware()));
    }

    public function boot(Router $router, Routes $routes): void
    {
        $router->patterns($routes->tokens);

        $this->bootMiddleware($router);
        $this->loadRoutesFrom(dirname(__DIR__).'/../routes/routes.php');

        $this->app->booted(function () use ($routes, $router): void {
            if (! Info::isInstalled()) {
                return;
            }

            $routes->getProjectConfigRoutes()->each(
                fn (Route $route) => $router->view($route->getUri(), $route->template),
            );
        });

        Event::listen(SiteDeleted::class, function (SiteDeleted $event) use ($routes): void {
            if (ProjectConfig::isApplyingExternalChanges()) {
                return;
            }

            $routes->handleDeletedSite($event);
        });
    }

    private function bootMiddleware(Router $router): void
    {
        collect([
            SetCraftGuard::class,
            UpdateLocale::class,
            CheckSchemaVersion::class,
            CheckForUpdates::class,
            SendPoweredByHeader::class,
            FlushProjectConfig::class,
        ])->each(fn ($middleware) => $router->pushMiddlewareToGroup('craft', $middleware));

        collect([
            RequireCpRequest::class,
            CheckRequirements::class,
            HandleInertiaRequests::class,
        ])->each(fn ($middleware) => $router->pushMiddlewareToGroup('craft.cp', $middleware));

        collect([
            'web',
        ])->each(fn ($middleware) => $router->pushMiddlewareToGroup('craft.web', $middleware));
    }
}
