<?php

declare(strict_types=1);

namespace CraftCms\Cms\Route;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\ConfigSyncController;
use CraftCms\Cms\Http\Controllers\MigrateController;
use CraftCms\Cms\Http\Controllers\PluginStore\InstallController as PluginStoreInstallController;
use CraftCms\Cms\Http\Controllers\PluginStore\RemoveController as PluginStoreRemoveController;
use CraftCms\Cms\Http\Controllers\Updates\UpdaterController;
use CraftCms\Cms\Http\Middleware\AddLogContext;
use CraftCms\Cms\Http\Middleware\CheckForUpdates;
use CraftCms\Cms\Http\Middleware\CheckRequirements;
use CraftCms\Cms\Http\Middleware\CheckSchemaVersion;
use CraftCms\Cms\Http\Middleware\Enforce2fa;
use CraftCms\Cms\Http\Middleware\EnforceLicenses;
use CraftCms\Cms\Http\Middleware\ExtractNamespace;
use CraftCms\Cms\Http\Middleware\HandleActionRequest;
use CraftCms\Cms\Http\Middleware\HandleInertiaRequests;
use CraftCms\Cms\Http\Middleware\HandleMatchedElementRoute;
use CraftCms\Cms\Http\Middleware\HandleTemplateRequest;
use CraftCms\Cms\Http\Middleware\HandleTokenRequest;
use CraftCms\Cms\Http\Middleware\RequireCpRequest;
use CraftCms\Cms\Http\Middleware\RunQueue;
use CraftCms\Cms\Http\Middleware\SendPoweredByHeader;
use CraftCms\Cms\Http\Middleware\SetCraftGuard;
use CraftCms\Cms\Http\Middleware\SetHeaders;
use CraftCms\Cms\Http\Middleware\UpdateLocale;
use CraftCms\Cms\Route\Data\Route;
use CraftCms\Cms\Site\Events\SiteDeleted;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Str;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as LaravelRouteServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Override;

class RouteServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        /**
         * These middleware must run before all others
         * as they rewrite the incoming request.
         */
        $kernel = $this->app->get(HttpKernel::class);
        $kernel->setGlobalMiddleware(array_merge([
            ExtractNamespace::class,
            HandleTokenRequest::class,
            HandleActionRequest::class,
        ], $kernel->getGlobalMiddleware()));

        LaravelRouteServiceProvider::loadCachedRoutesUsing(function (): void {
            require $this->app->getCachedRoutesPath();

            $this->bootMaintenanceModeExceptions();
            $this->bootRequestForgeryExceptions();
        });
    }

    public function boot(Router $router, Routes $routes): void
    {
        $router->patterns($routes->tokens);

        $this->bootMiddleware($router);
        $this->loadRoutesFrom(dirname(__DIR__).'/../routes/routes.php');

        if (! $this->app->routesAreCached()) {
            $this->bootMaintenanceModeExceptions();
            $this->bootRequestForgeryExceptions();
        }

        $this->app->booted(function () use ($routes, $router): void {
            if (! Cms::isInstalled()) {
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

    private function bootRequestForgeryExceptions(): void
    {
        /**
         * Actions that should not have CSRF token verification. These are automatically
         * mapped to `/{cpTrigger}/{actionTrigger}/route` and `/{actionTrigger}/{route}`
         */
        PreventRequestForgery::except(collect([
            'graphql/api',
            'preview/preview',
        ])->flatMap(fn (string $route) => [
            $route,
            Cms::config()->actionTrigger.Str::start($route, '/'),
            Cms::config()->cpTrigger.'/'.Cms::config()->actionTrigger.Str::start($route, '/'),
        ])->all());
    }

    /**
     * Register routes that should remain accessible during maintenance mode.
     */
    private function bootMaintenanceModeExceptions(): void
    {
        PreventRequestsDuringMaintenance::except([
            action([UpdaterController::class, 'precheck']),
            action([UpdaterController::class, 'finish']),
            action([UpdaterController::class, 'backup']),
            action([UpdaterController::class, 'serverCheck']),
            action([UpdaterController::class, 'migrate']),
            action([ConfigSyncController::class, 'finish']),
            action([PluginStoreInstallController::class, 'finish']),
            action([PluginStoreRemoveController::class, 'finish']),
            action(MigrateController::class),
        ]);
    }

    private function bootMiddleware(Router $router): void
    {
        collect([
            AddLogContext::class,
            SetCraftGuard::class,
            UpdateLocale::class,
            CheckSchemaVersion::class,
            CheckForUpdates::class,
            SendPoweredByHeader::class,
            Enforce2fa::class,
            SetHeaders::class,
        ])->each(fn (string $middleware) => $router->pushMiddlewareToGroup('craft', $middleware));

        collect([
            RequireCpRequest::class,
            CheckRequirements::class,
            HandleInertiaRequests::class,
            EnforceLicenses::class,
            HandleTemplateRequest::class,
        ])->each(fn (string $middleware) => $router->pushMiddlewareToGroup('craft.cp', $middleware));

        collect([
            'web',
            AuthenticateSession::class,
            RunQueue::class,
            HandleMatchedElementRoute::class,
            HandleTemplateRequest::class,
        ])->each(fn (string $middleware) => $router->pushMiddlewareToGroup('craft.web', $middleware));
    }
}
