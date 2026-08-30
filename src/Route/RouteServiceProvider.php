<?php

declare(strict_types=1);

namespace CraftCms\Cms\Route;

use CraftCms\Cms\Auth\Enums\CpAuthPath;
use CraftCms\Cms\Auth\LoginRateLimiter;
use CraftCms\Cms\Auth\TwoFactorRateLimiter;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\ConfigSyncController;
use CraftCms\Cms\Http\Controllers\MigrateController;
use CraftCms\Cms\Http\Controllers\PluginStore\InstallController as PluginStoreInstallController;
use CraftCms\Cms\Http\Controllers\PluginStore\RemoveController as PluginStoreRemoveController;
use CraftCms\Cms\Http\Controllers\Updates\UpdaterController;
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
use Illuminate\Foundation\Events\MaintenanceModeEnabled;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as LaravelRouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Routing\Router;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Uri;
use Override;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
        URL::defaults([
            'cpTrigger' => trim((string) Cms::config()->cpTrigger, '/') ?: null,
            'actionTrigger' => trim(Cms::config()->actionTrigger, '/'),
        ]);

        $router->patterns($routes->tokens);

        $this->bootMiddleware($router);
        $this->loadRoutesFrom(dirname(__DIR__).'/../routes/routes.php');

        if (! $this->app->routesAreCached()) {
            $this->bootMaintenanceModeExceptions();
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

    /**
     * Register routes that should remain accessible during maintenance mode.
     */
    private function bootMaintenanceModeExceptions(): void
    {
        $routes = app(Routes::class);
        $cpTrigger = trim((string) Cms::config()->cpTrigger, '/');
        $actionTrigger = $routes->actionTriggerUriPrefix();
        $cpActionTrigger = $routes->cpActionTriggerUriPrefix();

        $updatePaths = collect([
            [UpdaterController::class, 'precheck'],
            [UpdaterController::class, 'composerInstall'],
            [UpdaterController::class, 'finish'],
            [UpdaterController::class, 'backup'],
            [UpdaterController::class, 'serverCheck'],
            [UpdaterController::class, 'migrate'],
            [ConfigSyncController::class, 'finish'],
            [PluginStoreInstallController::class, 'finish'],
            [PluginStoreRemoveController::class, 'finish'],
            MigrateController::class,
        ])->map(fn (array|string $action) => Uri::action($action)->path());

        $authenticationActions = collect([
            'auth/verify-totp',
            'auth/verify-recovery-code',
            'auth/passkey-request-options',
            'users/login',
            'users/login-with-passkey',
            'users/login-modal',
            'users/redirect',
            'users/session-info',
            'users/set-password',
            'users/verify-email',
            'users/send-password-reset-email',
        ]);

        $cpAuthenticationPaths = collect([
            ...array_map(
                fn (CpAuthPath $path) => $routes->joinRoutePrefix([$cpTrigger, $path->value]),
                [
                    CpAuthPath::Login,
                    CpAuthPath::TwoFactorChallenge,
                    CpAuthPath::Logout,
                    CpAuthPath::SetPassword,
                    CpAuthPath::VerifyEmail,
                ],
            ),
            $routes->joinRoutePrefix([$cpTrigger, 'oauth/*/redirect']),
        ])->merge($authenticationActions->map(
            fn (string $path) => $routes->joinRoutePrefix([$cpActionTrigger, $path]),
        ));

        $siteAuthenticationPaths = collect([
            CpAuthPath::TwoFactorChallenge->value,
            'oauth/*/redirect',
            'oauth/*/callback',
            '.well-known/change-password',
        ])->merge($authenticationActions->map(
            fn (string $path) => $routes->joinRoutePrefix([$actionTrigger, $path]),
        ));

        if (Cms::isInstalled()) {
            $siteAuthenticationPaths = $siteAuthenticationPaths->merge(
                collect([
                    'getLoginPath',
                    'getVerifyEmailPath',
                    'getSetPasswordPath',
                    'getLogoutPath',
                ])->flatMap(fn (string $getter) => $routes->localizedConfigPaths($getter)),
            );
        }

        $runtimeCpExceptions = $updatePaths->merge($cpAuthenticationPaths);
        $runtimeExceptions = $runtimeCpExceptions->merge($siteAuthenticationPaths);

        PreventRequestsDuringMaintenance::skipWhen(function (Request $request) use ($runtimeExceptions): bool {
            $router = app(Router::class);
            $route = $request->route();

            if (! $route instanceof LaravelRoute) {
                try {
                    $route = $router->getRoutes()->match($request);
                } catch (HttpExceptionInterface) {
                    return false;
                }

                return in_array(
                    PreventRequestsDuringMaintenance::class,
                    $router->gatherRouteMiddleware($route),
                    true,
                );
            }

            if ($request->is(...$runtimeExceptions->all())) {
                return true;
            }

            return $request->getHadToken()
                || Context::getHidden(ResolveSite::HAD_SITE_TOKEN_KEY) === true
                || Gate::check($request->isCpRequest()
                    ? 'accessCpWhenSystemIsOff'
                    : 'accessSiteWhenSystemIsOff');
        });

        $preRenderExceptions = $runtimeExceptions
            ->concat([$routes->joinRoutePrefix([$cpTrigger, 'settings/general'])])
            ->all();

        Event::listen(MaintenanceModeEnabled::class, function () use ($preRenderExceptions): void {
            $maintenanceMode = $this->app->maintenanceMode();
            $data = $maintenanceMode->data();
            $data['except'] = array_values(array_unique([
                ...(array) ($data['except'] ?? []),
                ...$preRenderExceptions,
            ]));
            $maintenanceMode->activate($data);
        });
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
            PreventRequestsDuringMaintenance::class,
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
