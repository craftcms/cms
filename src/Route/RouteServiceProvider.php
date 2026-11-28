<?php

declare(strict_types=1);

namespace CraftCms\Cms\Route;

use CraftCms\Cms\Auth\DefaultRateLimit;
use CraftCms\Cms\Auth\LoginRateLimiter;
use CraftCms\Cms\Auth\OAuthServer\OAuthResources;
use CraftCms\Cms\Auth\TwoFactorRateLimiter;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Middleware\AddLogContext;
use CraftCms\Cms\Http\Middleware\AuthenticateCraftSession;
use CraftCms\Cms\Http\Middleware\CheckForUpdates;
use CraftCms\Cms\Http\Middleware\CheckRequirements;
use CraftCms\Cms\Http\Middleware\CollectCacheInfo;
use CraftCms\Cms\Http\Middleware\Enforce2fa;
use CraftCms\Cms\Http\Middleware\EnforceLicenses;
use CraftCms\Cms\Http\Middleware\EnsureInstalled;
use CraftCms\Cms\Http\Middleware\ExtractNamespace;
use CraftCms\Cms\Http\Middleware\ForgetTriggerParameters;
use CraftCms\Cms\Http\Middleware\HandleActionRequest;
use CraftCms\Cms\Http\Middleware\HandleInertiaRequests;
use CraftCms\Cms\Http\Middleware\HandleTemplateRequest;
use CraftCms\Cms\Http\Middleware\HandleTokenRequest;
use CraftCms\Cms\Http\Middleware\PreventRequestForgery as CraftPreventRequestForgery;
use CraftCms\Cms\Http\Middleware\PreventRequestsDuringMaintenance as CraftMaintenanceMiddleware;
use CraftCms\Cms\Http\Middleware\PreventStaleDatabaseReads;
use CraftCms\Cms\Http\Middleware\RenderBridgedScreen;
use CraftCms\Cms\Http\Middleware\RequireConfirmedPassword;
use CraftCms\Cms\Http\Middleware\RequireCpRequest;
use CraftCms\Cms\Http\Middleware\ResolveElementRoute;
use CraftCms\Cms\Http\Middleware\ResolveSite;
use CraftCms\Cms\Http\Middleware\RunQueue;
use CraftCms\Cms\Http\Middleware\SendBroadcasts;
use CraftCms\Cms\Http\Middleware\SetHeaders;
use CraftCms\Cms\Http\Middleware\ShowBrokenImage;
use CraftCms\Cms\Http\Middleware\StartLazySession;
use CraftCms\Cms\Http\Middleware\StartSessionWithoutPersistence;
use CraftCms\Cms\Http\Middleware\StripSiteBasePath;
use CraftCms\Cms\Http\Middleware\TrackSessionActivity;
use CraftCms\Cms\Http\Middleware\UpdateLocale;
use CraftCms\Cms\Http\Middleware\UseCraftAuthGuard;
use CraftCms\Cms\Http\Routing\UrlGenerator;
use CraftCms\Cms\Route\Data\Route;
use CraftCms\Cms\Site\Events\SiteDeleted;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Str;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as LaravelMaintenanceMiddleware;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as LaravelRouteServiceProvider;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Router;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Override;

/**
 * @since 6.0.0
 */
class RouteServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        CraftMaintenanceMiddleware::registerRouteMacro();
        StartLazySession::registerRouteMacro();
        UrlGenerator::registerRouteMacro();

        // Laravel's own `url` extenders, such as the signed URL key resolver, still apply to it
        $this->app->singleton('url', function (Application $app): UrlGenerator {
            $routes = $app->make(Router::class)->getRoutes();

            // Rebinding `routes` resolves `url` again, so only bind it the first time
            if (! $app->bound('routes') || $app->make('routes') !== $routes) {
                $app->instance('routes', $routes);
            }

            return new UrlGenerator(
                $routes,
                $app->rebinding('request', fn (Application $app, Request $request) => $app->make(\Illuminate\Contracts\Routing\UrlGenerator::class)->setRequest($request)),
                $app->make(Repository::class)->get('app.asset_url'),
            );
        });

        $this->app->bind(PreventRequestForgery::class, CraftPreventRequestForgery::class);

        /**
         * These middleware must run before all others
         * as they rewrite the incoming request.
         */
        $kernel = $this->app->get(HttpKernel::class);
        $kernel->addToMiddlewarePriorityAfter(StartSession::class, StartSessionWithoutPersistence::class);
        $kernel->addToMiddlewarePriorityAfter(StartSession::class, StartLazySession::class);
        $kernel->addToMiddlewarePriorityBefore(AuthenticatesRequests::class, UseCraftAuthGuard::class);
        // Before `auth`, so guests are sent to the requested site's login URL.
        $kernel->addToMiddlewarePriorityBefore(AuthenticatesRequests::class, ResolveSite::class);
        $kernel->addToMiddlewarePriorityAfter(SubstituteBindings::class, ResolveElementRoute::class);
        $kernel->addToMiddlewarePriorityAfter([StartSession::class, StartSessionWithoutPersistence::class, StartLazySession::class], PreventStaleDatabaseReads::class);
        $globalMiddleware = array_map(
            fn (string $middleware): string => $middleware === LaravelMaintenanceMiddleware::class
                ? CraftMaintenanceMiddleware::class
                : $middleware,
            $kernel->getGlobalMiddleware(),
        );
        $trustsProxies = fn (string $middleware): bool => is_a($middleware, TrustProxies::class, true);
        $kernel->setGlobalMiddleware([
            // First, so StripSiteBasePath finds the requested site from the forwarded host
            ...array_filter($globalMiddleware, $trustsProxies),
            ExtractNamespace::class,
            HandleTokenRequest::class,
            StripSiteBasePath::class,
            HandleActionRequest::class,
            // After the request is rewritten, and outside sessions and exception rendering, so it sees the final response
            CollectCacheInfo::class,
            ...array_filter($globalMiddleware, fn (string $middleware): bool => ! $trustsProxies($middleware)),
        ]);

        LaravelRouteServiceProvider::loadCachedRoutesUsing(function (): void {
            $templates = require $this->app->getCachedRoutesPath();

            CraftMaintenanceMiddleware::registerRouteExceptions(is_array($templates) ? $templates : null);
            $this->bootRequestForgeryExceptions();
        });
    }

    public function boot(Router $router, Routes $routes): void
    {
        Event::listen(RouteMatched::class, function (RouteMatched $event): void {
            MatchedElement::set(null);
            StartLazySession::applyTo($event->route);

            if (Cms::isInstalled() && $event->request->isSiteRequest() && ! $event->request->isActionRequest() && ! Cms::config()->headlessMode) {
                $event->route->middleware([ResolveSite::class, ResolveElementRoute::class]);
            }
        });

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

        $this->app->booted(function (): void {
            // Project routes are registered at runtime so cached routes cannot retain stale project config.
            if (! Cms::isInstalled() || $this->app->runningConsoleCommand('route:cache')) {
                return;
            }

            $this->app->make(ProjectConfigRoutes::class)->register();
        });

        Event::listen(SiteDeleted::class, function (SiteDeleted $event): void {
            if (ProjectConfig::isApplyingExternalChanges()) {
                return;
            }

            $this->app->make(Routes::class)->handleDeletedSite($event);
        });

        RateLimiter::for('password-reset', function ($request) {
            $loginName = $request->input('loginName');

            // Don't throttle if no loginName provided - validation will handle it
            if (empty($loginName)) {
                return Limit::none();
            }

            // Throttle to prevent enumeration attacks
            return DefaultRateLimit::for($request) ?? Limit::none();
        });

        RateLimiter::for(
            OAuthResources::REGISTRATION_RATE_LIMITER,
            fn (Request $request) => Limit::perHour(10)->by($request->ip() ?? 'unknown'),
        );

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
        if (Cms::config()->authGuard === null) {
            $router->aliasMiddleware('password.confirm', RequireConfirmedPassword::class);
        }

        collect([
            PreventStaleDatabaseReads::class,
            ForgetTriggerParameters::class,
            EnsureInstalled::class,
            AddLogContext::class,
            ResolveSite::class,
            UpdateLocale::class,
            CheckForUpdates::class,
            Enforce2fa::class,
            TrackSessionActivity::class,
            SetHeaders::class,
            ShowBrokenImage::class,
            CraftMaintenanceMiddleware::class,
        ])->each(fn (string $middleware) => $router->pushMiddlewareToGroup('craft', $middleware));

        $router->prependMiddlewareToGroup('craft', UseCraftAuthGuard::class);

        collect([
            RequireCpRequest::class,
            AuthenticateCraftSession::class,
            CheckRequirements::class,
            // Outside `HandleInertiaRequests`, so it sees what the page delivered.
            SendBroadcasts::class,
            HandleInertiaRequests::class,
            EnforceLicenses::class,
            HandleTemplateRequest::class,
            // Last, so the response it builds passes back out through
            // `HandleInertiaRequests`.
            RenderBridgedScreen::class,
        ])->each(fn (string $middleware) => $router->pushMiddlewareToGroup('craft.cp', $middleware));

        collect([
            'web',
            AuthenticateCraftSession::class,
            RunQueue::class,
            HandleTemplateRequest::class,
        ])->each(fn (string $middleware) => $router->pushMiddlewareToGroup('craft.web', $middleware));
    }
}
