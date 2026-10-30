<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\UploadSessionController;
use CraftCms\Cms\Http\Middleware\AddLogContext;
use CraftCms\Cms\Http\Middleware\EnsureInstalled;
use CraftCms\Cms\Http\Middleware\ForgetTriggerParameters;
use CraftCms\Cms\Http\Middleware\HandleInertiaRequests;
use CraftCms\Cms\Http\Middleware\PreventStaleDatabaseReads;
use CraftCms\Cms\Http\Middleware\ResolveSite;
use CraftCms\Cms\Mcp\Enums\McpScope;
use CraftCms\Cms\Mcp\Http\Controllers\EndpointPageController;
use CraftCms\Cms\Mcp\Http\Controllers\McpController;
use CraftCms\Cms\Mcp\Http\Controllers\OAuthApproveController;
use CraftCms\Cms\Mcp\Http\Controllers\OAuthAuthorizationController;
use CraftCms\Cms\Mcp\Http\Controllers\OAuthMetadataController;
use CraftCms\Cms\Mcp\Http\Controllers\OAuthRegisterController;
use CraftCms\Cms\Mcp\Http\Middleware\AddOAuthChallenge;
use CraftCms\Cms\Mcp\Http\Middleware\AuthorizeCpRequests;
use CraftCms\Cms\Mcp\Http\Middleware\ReorderJsonAccept;
use CraftCms\Cms\Mcp\Http\Middleware\RequireAccessDuringMaintenance;
use CraftCms\Cms\Mcp\Http\Middleware\SetActivityOrigin;
use Illuminate\Routing\Router;
use Laravel\Passport\Http\Controllers\DenyAuthorizationController;
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
            AddOAuthChallenge::class,
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
                    $router->any('{upload}/transfer', [UploadSessionController::class, 'transfer'])
                        ->whereUuid('upload')
                        ->name('transfer');
                    $router->get('{upload}', [UploadSessionController::class, 'status'])
                        ->whereUuid('upload')
                        ->name('status');
                    $router->delete('{upload}', [UploadSessionController::class, 'destroy'])
                        ->whereUuid('upload')
                        ->name('destroy');
                });
        }

        if ($authorizationRoute !== null) {
            $this->registerOAuth($authorizationRoute, $settings->isCpRoute($authorizationRoute));
        }

        // Routes are named after they're added, and this can run after the application has booted.
        $this->router->getRoutes()->refreshNameLookups();
        $this->router->getRoutes()->refreshActionLookups();
    }

    private function path(bool $enabled, string $route): ?string
    {
        $path = trim($route, '/');

        return $enabled && $path !== '' && $this->conflicts->find($path) === null ? $path : null;
    }

    private function registerEndpoint(string $name, string $path, bool $authenticated, bool $public, bool $cp): void
    {
        $this->router->get($path, [EndpointPageController::class, $authenticated ? 'authenticated' : 'public'])
            ->middleware(['web', 'craft', ...($cp ? [HandleInertiaRequests::class] : []), ...($authenticated ? [] : ['throttle:60,1'])])
            ->defaults('mcp', true)
            ->name("$name.page");

        $this->router->options($path, [McpController::class, 'preflight'])
            ->middleware(self::publicMiddleware())
            ->defaults('mcp', true);

        $server = match (true) {
            $authenticated && $public => $this->router->allowDuringMaintenance()->match(['POST', 'DELETE'], $path, [McpController::class, 'shared']),
            $authenticated => $this->router->allowDuringMaintenance()->match(['POST', 'DELETE'], $path, [McpController::class, 'authenticated'])
                ->middleware(self::authenticatedMiddleware()),
            default => $this->router->match(['POST', 'DELETE'], $path, [McpController::class, 'public'])
                ->middleware(self::publicMiddleware()),
        };

        $server->defaults('mcp', true)->name($name);
    }

    private function registerOAuth(string $authorizationRoute, bool $cp): void
    {
        $basePath = trim((string) parse_url((string) config('app.url'), PHP_URL_PATH), '/');
        $resourcePath = ltrim("$basePath/$authorizationRoute", '/');

        $this->router->get(".well-known/oauth-protected-resource/$resourcePath", [OAuthMetadataController::class, 'resource'])
            ->middleware(EnsureInstalled::class)
            ->name('craft.mcp.oauth.resource');
        $this->router->get(".well-known/oauth-authorization-server/$resourcePath", [OAuthMetadataController::class, 'authorizationServer'])
            ->middleware(EnsureInstalled::class)
            ->name('craft.mcp.oauth.authorization-server');

        $this->router->prefix("$authorizationRoute/oauth")->name('craft.mcp.oauth.')->group(function (Router $router) use ($cp): void {
            $router->post('register', OAuthRegisterController::class)
                ->middleware([EnsureInstalled::class, PreventStaleDatabaseReads::class, 'throttle:60,1'])
                ->name('register');

            $router->middleware(['web', 'craft', ...($cp ? [HandleInertiaRequests::class] : []), 'throttle:60,1', AuthorizeCpRequests::class])->group(function (Router $router): void {
                $router->get('authorize', OAuthAuthorizationController::class)
                    ->name('authorize');

                $router->middleware(['auth:'.Cms::config()->getAuthGuard()])->group(function (Router $router): void {
                    $router->post('authorize', [OAuthApproveController::class, 'approve'])->name('approve');
                    $router->delete('authorize', [DenyAuthorizationController::class, 'deny'])->name('deny');
                });
            });
        });
    }
}
