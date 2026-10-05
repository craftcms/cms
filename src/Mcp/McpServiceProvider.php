<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Middleware\AddLogContext;
use CraftCms\Cms\Http\Middleware\EnsureInstalled;
use CraftCms\Cms\Http\Middleware\ResolveSite;
use CraftCms\Cms\Http\Middleware\UseWriteConnection;
use CraftCms\Cms\Mcp\Http\Controllers\McpController;
use CraftCms\Cms\Mcp\Http\Middleware\UseDebugMcpUser;
use CraftCms\Cms\Mcp\Http\Responses\AuthorizationView;
use CraftCms\Cms\Route\Routes;
use CraftCms\Cms\User\Data\Permission;
use CraftCms\Cms\User\Data\PermissionGroup;
use CraftCms\Cms\User\UserPermissions;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Contracts\AuthorizationViewResponse;
use Laravel\Passport\Http\Middleware\CheckToken;
use Laravel\Passport\Passport;
use Laravel\Passport\Scope;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class McpServiceProvider extends ServiceProvider
{
    private const string SCOPE = 'craft:mcp';

    public function register(): void
    {
        if ($this->app->bound(AuthorizationViewResponse::class)) {
            return;
        }

        Passport::authorizationView(
            fn (array $parameters): Response => $this->app->make(AuthorizationView::class)($parameters),
        );
    }

    public function boot(
        UserPermissions $userPermissions,
        Router $router,
        Routes $routes,
    ): void {
        $this->app->booted(function (): void {
            $scopes = Passport::scopes()
                ->mapWithKeys(fn (Scope $scope): array => [$scope->id => $scope->description])
                ->all();

            Passport::tokensCan([
                ...$scopes,
                self::SCOPE => t('Use Craft MCP'),
            ]);
        });

        $userPermissions->registerPermissionGroup('mcp', static fn (): PermissionGroup => new PermissionGroup(
            handle: 'mcp',
            heading: t('MCP'),
            permissions: collect([
                new Permission('useCraftMcp', t('Use Craft MCP')),
            ]),
        ));

        if (! $this->app->routesAreCached()) {
            $this->registerRoutes($router, $routes);
        }
    }

    private function registerRoutes(Router $router, Routes $routes): void
    {
        $config = Cms::config()->mcp;
        $authentication = $this->app->hasDebugModeEnabled() && ! is_null($config->debugUserId)
            ? [UseDebugMcpUser::class]
            : ['auth:craft-mcp', CheckToken::using(self::SCOPE)];
        $middleware = [
            EnsureInstalled::class,
            AddLogContext::class,
            ResolveSite::class,
            UseWriteConnection::class,
        ];

        $router
            ->prefix($routes->cpTriggerRoutePrefix())
            ->name('craft.cp.')
            ->group(function (Router $router) use ($authentication, $config, $middleware): void {
                $router->options($config->endpoint, [McpController::class, 'admin'])
                    ->middleware($middleware);

                $router->post($config->endpoint, [McpController::class, 'admin'])
                    ->middleware([
                        ...$middleware,
                        ...$authentication,
                        'can:accessCp',
                        'can:useCraftMcp',
                        ...$config->middleware,
                    ])
                    ->name('mcp.server');
            });
    }
}
