<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use CraftCms\Cms\Auth\OAuthServer\Http\Middleware\AddOAuthChallenge;
use CraftCms\Cms\Auth\OAuthServer\OAuthResources;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\UploadSessionController;
use CraftCms\Cms\Http\Middleware\AddLogContext;
use CraftCms\Cms\Http\Middleware\AuthorizeCpRequests;
use CraftCms\Cms\Http\Middleware\EnsureInstalled;
use CraftCms\Cms\Http\Middleware\ForgetTriggerParameters;
use CraftCms\Cms\Http\Middleware\HandleInertiaRequests;
use CraftCms\Cms\Http\Middleware\PreventStaleDatabaseReads;
use CraftCms\Cms\Http\Middleware\ResolveSite;
use CraftCms\Cms\Mcp\Enums\McpScope;
use CraftCms\Cms\Mcp\Http\Controllers\EndpointPageController;
use CraftCms\Cms\Mcp\Http\Controllers\McpController;
use CraftCms\Cms\Mcp\Http\Middleware\ReorderJsonAccept;
use CraftCms\Cms\Mcp\Http\Middleware\RequireAccessDuringMaintenance;
use CraftCms\Cms\Mcp\Http\Middleware\SetActivityOrigin;
use Illuminate\Routing\Router;
use Laravel\Passport\Http\Middleware\CheckTokenForAnyScope;

/**
 * Registers the routes for the enabled MCP servers.
 *
 * When both servers share an endpoint, requests with a bearer token go to the authenticated server, and the rest go
 * to the public server.
 *
 * @since 6.0.0
 */
readonly class RouteRegistrar
{
    public function __construct(
        private Router $router,
        private EndpointConflicts $conflicts,
        private OAuthResources $resources,
    ) {}

    /** @return list<mixed> */
    public static function authenticatedMiddleware(): array
    {
        return [
            ForgetTriggerParameters::class,
            EnsureInstalled::class,
            AddLogContext::class,
            ResolveSite::class,
            PreventStaleDatabaseReads::class,
            AddOAuthChallenge::class.':mcp',
            ReorderJsonAccept::class,
            'auth:craft-mcp',
            CheckTokenForAnyScope::using(McpScope::Read->satisfyingValues()),
            AuthorizeCpRequests::class,
            RequireAccessDuringMaintenance::class,
            SetActivityOrigin::class,
            ...Cms::config()->mcp->middleware,
        ];
    }

    /** @return list<mixed> */
    public static function publicMiddleware(): array
    {
        return [
            EnsureInstalled::class,
            AddLogContext::class,
            ResolveSite::class,
            PreventStaleDatabaseReads::class,
            'throttle:60,1',
            RequireAccessDuringMaintenance::class,
            SetActivityOrigin::class,
        ];
    }

    public function register(): void
    {
        $settings = app(Settings::class);
        $route = $this->path($settings->enabled, $settings->route);
        $authorizationRoute = $this->path($settings->enabled, $settings->authorizationRoute);
        $publicRoute = $this->path($settings->publicEnabled, $settings->publicRoute);

        if ($settings->isCpRoute($settings->publicRoute)) {
            $publicRoute = null;
        }

        $paths = array_filter([
            'craft.mcp.server' => $route,
            'craft.mcp.authorization' => $authorizationRoute,
            'craft.mcp.public' => $publicRoute,
        ]);

        foreach (array_unique($paths) as $name => $path) {
            $authenticated = $path === $route || $path === $authorizationRoute;
            $this->registerEndpoint($name, $path, $authenticated, $path === $publicRoute, $settings->isCpRoute($path));
        }

        if ($route !== null) {
            $this->router->allowDuringMaintenance()
                ->prefix("$route/uploads")
                ->name('craft.mcp.uploads.')
                ->middleware([...self::authenticatedMiddleware(), CheckTokenForAnyScope::using(McpScope::Write->satisfyingValues())])
                ->group(function (Router $router): void {
                    $router->name('transfer')->any('{upload}/transfer', [UploadSessionController::class, 'transfer'])
                        ->whereUuid('upload');
                    $router->name('status')->get('{upload}', [UploadSessionController::class, 'status'])
                        ->whereUuid('upload');
                    $router->name('destroy')->delete('{upload}', [UploadSessionController::class, 'destroy'])
                        ->whereUuid('upload');
                });
        }

        if ($authorizationRoute !== null) {
            $this->resources->routes('mcp');
        }
    }

    private function path(bool $enabled, string $route): ?string
    {
        $path = trim($route, '/');

        return $enabled && $path !== '' && $this->conflicts->find($path) === null ? $path : null;
    }

    private function registerEndpoint(string $name, string $path, bool $authenticated, bool $public, bool $cp): void
    {
        $this->router->name("$name.page")->get($path, [EndpointPageController::class, $authenticated ? 'authenticated' : 'public'])
            ->middleware(['web', 'craft', ...($cp ? [HandleInertiaRequests::class] : []), ...($authenticated ? [] : ['throttle:60,1'])])
            ->defaults('mcp', true);

        $this->router->options($path, [McpController::class, 'preflight'])
            ->middleware(self::publicMiddleware())
            ->defaults('mcp', true);

        $server = match (true) {
            $authenticated && $public => $this->router->allowDuringMaintenance()->name($name)->match(['POST', 'DELETE'], $path, [McpController::class, 'shared']),
            $authenticated => $this->router->allowDuringMaintenance()->name($name)->match(['POST', 'DELETE'], $path, [McpController::class, 'authenticated'])
                ->middleware(self::authenticatedMiddleware()),
            default => $this->router->name($name)->match(['POST', 'DELETE'], $path, [McpController::class, 'public'])
                ->middleware(self::publicMiddleware()),
        };

        $server->defaults('mcp', true);
    }
}
