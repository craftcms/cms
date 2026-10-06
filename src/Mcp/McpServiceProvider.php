<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Settings as CpSettings;
use CraftCms\Cms\Http\Controllers\UploadSessionController;
use CraftCms\Cms\Http\Middleware\AddLogContext;
use CraftCms\Cms\Http\Middleware\EnsureInstalled;
use CraftCms\Cms\Http\Middleware\HandleInertiaRequests;
use CraftCms\Cms\Http\Middleware\ResolveSite;
use CraftCms\Cms\Http\Middleware\UseWriteConnection;
use CraftCms\Cms\Mcp\Commands\ServeCommand;
use CraftCms\Cms\Mcp\Http\Controllers\McpController;
use CraftCms\Cms\Mcp\Http\Controllers\OAuthAuthorizationController;
use CraftCms\Cms\Mcp\Http\Controllers\OAuthMetadataController;
use CraftCms\Cms\Mcp\Http\Controllers\OAuthRegisterController;
use CraftCms\Cms\Mcp\Http\Middleware\AddOAuthChallenge;
use CraftCms\Cms\Mcp\Http\Middleware\ReorderJsonAccept;
use CraftCms\Cms\Mcp\Http\Middleware\SetActivityOrigin;
use CraftCms\Cms\Mcp\Http\Responses\AuthorizationView;
use CraftCms\Cms\Mcp\OAuth\Metadata;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Route\Routes;
use CraftCms\Cms\User\Data\Permission;
use CraftCms\Cms\User\Data\PermissionGroup;
use CraftCms\Cms\User\UserPermissions;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Contracts\AuthorizationViewResponse;
use Laravel\Passport\Http\Controllers\ApproveAuthorizationController;
use Laravel\Passport\Http\Controllers\DenyAuthorizationController;
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
    public function register(): void
    {
        $kernel = $this->app->make(Kernel::class);
        $kernel->addToMiddlewarePriorityBefore(AuthenticatesRequests::class, AddOAuthChallenge::class);
        $kernel->addToMiddlewarePriorityBefore(AuthenticatesRequests::class, ReorderJsonAccept::class);

        $this->app->scoped(Settings::class, fn (): Settings => new Settings(
            $this->app->make(ProjectConfig::class)->get('mcp') ?? [],
        ));

        if ($this->app->bound(AuthorizationViewResponse::class)) {
            return;
        }

        Passport::authorizationView(
            fn (array $parameters): Response => $this->app->make(AuthorizationView::class)($parameters),
        );
    }

    public function boot(
        CpSettings $cpSettings,
        PublicRouteRegistrar $publicRoutes,
        UserPermissions $userPermissions,
        Router $router,
        Routes $routes,
        Metadata $metadata,
    ): void {
        $this->commands([
            ServeCommand::class,
        ]);

        $this->app->booted(function (): void {
            $scopes = Passport::scopes()
                ->mapWithKeys(fn (Scope $scope): array => [$scope->id => $scope->description])
                ->all();

            Passport::tokensCan([
                ...$scopes,
                Metadata::SCOPE => t('Use Craft MCP'),
            ]);
        });

        $userPermissions->registerPermissionGroup('mcp', static fn (): PermissionGroup => new PermissionGroup(
            handle: 'mcp',
            heading: t('MCP'),
            permissions: collect([
                new Permission('useCraftMcp', t('Use Craft MCP')),
            ]),
        ));

        $settings = static fn (): array => [
            'label' => t('MCP'),
            'url' => route('craft.cp.settings.mcp.index'),
            'iconName' => 'light/robot',
        ];
        $cpSettings->registerSetting('System', 'mcp', $settings);
        $cpSettings->registerReadOnlySetting('System', 'mcp', $settings);

        if (! $this->app->routesAreCached()) {
            $this->registerRoutes($router, $routes, $metadata);
            $publicRoutes->register();
        }
    }

    private function registerRoutes(Router $router, Routes $routes, Metadata $metadata): void
    {
        $config = Cms::config()->mcp;
        $middleware = [
            EnsureInstalled::class,
            AddLogContext::class,
            ResolveSite::class,
            UseWriteConnection::class,
        ];
        $authenticatedMiddleware = [
            ...$middleware,
            AddOAuthChallenge::class,
            ReorderJsonAccept::class,
            'auth:craft-mcp',
            CheckToken::using(Metadata::SCOPE),
            'can:accessCp',
            'can:useCraftMcp',
            SetActivityOrigin::class,
            ...$config->middleware,
        ];

        $resourcePath = $routes->joinRoutePrefix([
            trim((string) parse_url($metadata->baseUrl(), PHP_URL_PATH), '/'),
            $routes->cpTriggerRoutePrefix(),
            trim($config->endpoint, '/'),
        ]);
        $router->get('/.well-known/oauth-protected-resource/'.$resourcePath, [OAuthMetadataController::class, 'resource'])
            ->middleware(EnsureInstalled::class)
            ->name('craft.mcp.oauth.resource');
        $router->get('/.well-known/oauth-authorization-server/'.$resourcePath, [OAuthMetadataController::class, 'authorizationServer'])
            ->middleware(EnsureInstalled::class)
            ->name('craft.mcp.oauth.authorization-server');

        $router
            ->prefix($routes->cpTriggerRoutePrefix())
            ->name('craft.cp.')
            ->group(function (Router $router) use ($authenticatedMiddleware, $config, $middleware): void {
                $router->prefix($config->endpoint.'/oauth')->name('mcp.oauth.')->group(function (Router $router): void {
                    $router->post('register', OAuthRegisterController::class)
                        ->middleware([EnsureInstalled::class, UseWriteConnection::class, 'throttle:60,1'])
                        ->name('register');

                    $router->middleware(['web', 'craft', HandleInertiaRequests::class, 'throttle:60,1'])->group(function (Router $router): void {
                        $router->get('authorize', OAuthAuthorizationController::class)
                            ->name('authorize');

                        $router->middleware(['auth:'.Cms::config()->getAuthGuard(), 'can:accessCp', 'can:useCraftMcp'])->group(function (Router $router): void {
                            $router->post('authorize', [ApproveAuthorizationController::class, 'approve'])->name('approve');
                            $router->delete('authorize', [DenyAuthorizationController::class, 'deny'])->name('deny');
                        });
                    });
                });

                $router->options($config->endpoint, [McpController::class, 'admin'])
                    ->middleware($middleware);

                $router->post($config->endpoint, [McpController::class, 'admin'])
                    ->middleware($authenticatedMiddleware)
                    ->name('mcp.server');

                $router
                    ->prefix("$config->endpoint/uploads")
                    ->name('mcp.uploads.')
                    ->middleware($authenticatedMiddleware)
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
            });
    }
}
