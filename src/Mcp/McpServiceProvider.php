<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use CraftCms\Cms\Cp\Settings as CpSettings;
use CraftCms\Cms\Mcp\Commands\ServeCommand;
use CraftCms\Cms\Mcp\Enums\McpScope;
use CraftCms\Cms\Mcp\Http\Middleware\AddOAuthChallenge;
use CraftCms\Cms\Mcp\Http\Middleware\ReorderJsonAccept;
use CraftCms\Cms\Mcp\Http\Responses\AuthorizationView;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Contracts\AuthorizationViewResponse;
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
        RouteRegistrar $mcpRoutes,
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
                ...collect(McpScope::cases())
                    ->mapWithKeys(fn (McpScope $scope): array => [$scope->value => $scope->description()])
                    ->all(),
            ]);
        });

        $settings = static fn (): array => [
            'label' => t('MCP'),
            'url' => route('craft.cp.settings.mcp.index'),
            'iconName' => 'light/robot',
        ];
        $cpSettings->registerSetting('Extend', 'mcp', $settings);
        $cpSettings->registerReadOnlySetting('Extend', 'mcp', $settings);

        if (! $this->app->routesAreCached()) {
            $mcpRoutes->register();
        }
    }
}
