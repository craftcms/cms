<?php

declare(strict_types=1);

namespace CraftCms\Cms\Route;

use CraftCms\Cms\Auth\LoginRateLimiter;
use CraftCms\Cms\Auth\TwoFactorRateLimiter;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Middleware\AddLogContext;
use CraftCms\Cms\Http\Middleware\CheckForUpdates;
use CraftCms\Cms\Http\Middleware\CheckRequirements;
use CraftCms\Cms\Http\Middleware\Enforce2fa;
use CraftCms\Cms\Http\Middleware\EnforceLicenses;
use CraftCms\Cms\Http\Middleware\EnsureInstalled;
use CraftCms\Cms\Http\Middleware\ExtractNamespace;
use CraftCms\Cms\Http\Middleware\ForgetTriggerParameters;
use CraftCms\Cms\Http\Middleware\HandleActionRequest;
use CraftCms\Cms\Http\Middleware\HandleInertiaRequests;
use CraftCms\Cms\Http\Middleware\HandleTemplateRequest;
use CraftCms\Cms\Http\Middleware\HandleTokenRequest;
use CraftCms\Cms\Http\Middleware\PreventRequestsDuringMaintenance as CraftMaintenanceMiddleware;
use CraftCms\Cms\Http\Middleware\RequireConfirmedPassword;
use CraftCms\Cms\Http\Middleware\RequireCpRequest;
use CraftCms\Cms\Http\Middleware\ResolveSite;
use CraftCms\Cms\Http\Middleware\RunQueue;
use CraftCms\Cms\Http\Middleware\SetHeaders;
use CraftCms\Cms\Http\Middleware\ShowBrokenImage;
use CraftCms\Cms\Http\Middleware\UpdateLocale;
use CraftCms\Cms\Http\Middleware\UseWriteConnection;
use CraftCms\Cms\Route\Data\Route;
use CraftCms\Cms\Site\Events\SiteDeleted;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Str;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as LaravelMaintenanceMiddleware;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as LaravelRouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Override;

class RouteServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        CraftMaintenanceMiddleware::registerRouteMacro();

        /**
         * These middleware must run before all others
         * as they rewrite the incoming request.
         */
        $kernel = $this->app->get(HttpKernel::class);
        $globalMiddleware = array_map(
            fn (string $middleware): string => $middleware === LaravelMaintenanceMiddleware::class
                ? CraftMaintenanceMiddleware::class
                : $middleware,
            $kernel->getGlobalMiddleware(),
        );
        $kernel->setGlobalMiddleware(array_merge([
            ExtractNamespace::class,
            HandleTokenRequest::class,
            HandleActionRequest::class,
        ], $globalMiddleware));

        LaravelRouteServiceProvider::loadCachedRoutesUsing(function (): void {
            require $this->app->getCachedRoutesPath();

            CraftMaintenanceMiddleware::registerRouteExceptions();
            $this->bootRequestForgeryExceptions();
        });
    }

    public function boot(Router $router, Routes $routes): void
    {
        URL::defaults([
            'cpTrigger' => trim((string) Cms::config()->cpTrigger, '/') ?: null,
            'actionTrigger' => trim(Cms::config()->actionTrigger, '/'),
        ]);

        $router->patterns($routes->tokens);

        $this->bootMiddleware($router);
        $this->loadRoutesFrom(dirname(__DIR__).'/../routes/routes.php');

        if (! $this->app->routesAreCached()) {
            CraftMaintenanceMiddleware::registerRouteExceptions();
            $this->bootRequestForgeryExceptions();
        }

        $this->app->booted(function () use ($routes, $router): void {
            // Project routes are registered at runtime so cached routes cannot retain stale project config.
            if (! Cms::isInstalled() || $this->app->runningConsoleCommand('route:cache')) {
                return;
            }

            $routes->getProjectConfigRoutes()->each(
                fn (Route $route) => $router
                    ->view($route->getUri(), $route->template)
                    ->middleware(['craft', 'craft.web']),
            );
        });

        Event::listen(SiteDeleted::class, function (SiteDeleted $event) use ($routes): void {
            if (ProjectConfig::isApplyingExternalChanges()) {
                return;
            }

            $routes->handleDeletedSite($event);
        });

        RateLimiter::for('password-reset', function ($request) {
            $loginName = $request->input('loginName');

            // Don't throttle if no loginName provided - validation will handle it
            if (empty($loginName)) {
                return Limit::none();
            }

            // Throttle to prevent enumeration attacks
            return Limit::perMinute(1)->by($request->ip());
        });

        RateLimiter::for(
            LoginRateLimiter::NAME,
            fn (Request $request) => app(LoginRateLimiter::class)->limit($request),
        );

        RateLimiter::for(
            TwoFactorRateLimiter::NAME,
            fn (Request $request) => app(TwoFactorRateLimiter::class)->limit($request),
        );
    }

    private function bootRequestForgeryExceptions(): void
    {
        /**
         * Actions that should not have CSRF token verification. These are automatically
         * mapped to `/{cpTrigger}/{actionTrigger}/route` and `/{actionTrigger}/{route}`
         */
        PreventRequestForgery::except(collect([
            'graphql/api',
        ])->flatMap(fn (string $route) => [
            $route,
            app(Routes::class)->actionTriggerUriPrefix().Str::start($route, '/'),
            app(Routes::class)->cpActionTriggerUriPrefix().Str::start($route, '/'),
        ])->all());
    }

    private function bootMiddleware(Router $router): void
    {
        $router->aliasMiddleware('password.confirm', RequireConfirmedPassword::class);

        collect([
            UseWriteConnection::class,
            ForgetTriggerParameters::class,
            EnsureInstalled::class,
            AddLogContext::class,
            ResolveSite::class,
            UpdateLocale::class,
            CheckForUpdates::class,
            Enforce2fa::class,
            SetHeaders::class,
            ShowBrokenImage::class,
            CraftMaintenanceMiddleware::class,
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
            HandleTemplateRequest::class,
        ])->each(fn (string $middleware) => $router->pushMiddlewareToGroup('craft.web', $middleware));
    }
}
