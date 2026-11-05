<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use CraftCms\Cms\Auth\DefaultRateLimit;
use CraftCms\Cms\Auth\OAuthServer\Http\Middleware\AddOAuthChallenge;
use CraftCms\Cms\Auth\OAuthServer\Http\Responses\AuthorizationView;
use CraftCms\Cms\Auth\OAuthServer\OAuthResource;
use CraftCms\Cms\Auth\OAuthServer\OAuthResources;
use CraftCms\Cms\Auth\PersonalAccessTokens\PersonalAccessTokens;
use CraftCms\Cms\Auth\PersonalAccessTokens\ScopeGroup;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Settings as CpSettings;
use CraftCms\Cms\Mcp\Commands\ServeCommand;
use CraftCms\Cms\Mcp\Enums\McpScope;
use CraftCms\Cms\Mcp\Http\Middleware\ReorderJsonAccept;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\User\Contracts\CraftUser;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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

        $this->app->scoped(Settings::class, function (): Settings {
            $projectConfig = $this->app->make(ProjectConfig::class);
            $cpTrigger = trim((string) Cms::config()->cpTrigger, '/');
            // Without public registration, the authenticated server defaults to the control panel, so the public
            // server can have `/mcp`.
            $defaults = $cpTrigger !== '' && ! $projectConfig->get('users.allowPublicRegistration')
                ? ['route' => "/{$cpTrigger}/mcp", 'authorizationRoute' => "/{$cpTrigger}/mcp/auth"]
                : [];

            return new Settings([...$defaults, ...($projectConfig->get('mcp') ?? [])]);
        });

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
        OAuthResources $oauthResources,
        PersonalAccessTokens $personalAccessTokens,
    ): void {
        $this->commands([
            ServeCommand::class,
        ]);

        // Like other default rate limits, each discovery route tracks its own requests
        RateLimiter::for(RouteRegistrar::DISCOVERY_RATE_LIMITER, function (Request $request): Limit {
            $limit = DefaultRateLimit::for($request);

            return $limit?->by($limit->key.'|'.$request->route()?->getName()) ?? Limit::none();
        });

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

        $oauthResources->register('mcp', function (): OAuthResource {
            $settings = $this->app->make(Settings::class);

            return new OAuthResource(
                handle: 'mcp',
                levels: collect(McpScope::cases())
                    ->mapWithKeys(fn (McpScope $scope): array => [$scope->value => ['label' => $scope->label(), 'description' => $scope->description()]])
                    ->all(),
                resourceUrl: $settings->url($settings->authorizationRoute),
                authorizationRoute: $settings->authorizationRoute,
                baseUrl: $settings->baseUrl($settings->authorizationRoute),
                userLevels: fn (CraftUser $user): array => $user->isAdmin() ? McpScope::values() : [McpScope::Read->value, McpScope::Write->value],
                routeName: 'craft.mcp.oauth',
            );
        });

        // MCP clients the user has authorized are listed on their Tokens screen, but MCP tokens come from MCP clients,
        // so users can’t grant MCP scopes to personal access tokens.
        $personalAccessTokens->registerScopeGroup('mcp', fn (): ?ScopeGroup => $this->app->make(Settings::class)->enabled ? new ScopeGroup(
            handle: 'mcp',
            label: t('MCP'),
            scopes: collect(McpScope::cases())
                ->mapWithKeys(fn (McpScope $scope): array => [$scope->value => $scope->description()])
                ->all(),
            userScopes: fn (): array => [],
        ) : null);

        $settings = static fn (): array => [
            'label' => t('MCP'),
            'url' => route('craft.cp.settings.mcp.index'),
            'iconName' => 'robot',
        ];
        $cpSettings->registerSetting('Extend', 'mcp', $settings);
        $cpSettings->registerReadOnlySetting('Extend', 'mcp', $settings);

        $this->app->booted(function () use ($mcpRoutes): void {
            // MCP routes are registered at runtime so cached routes cannot retain stale project config.
            if (! $this->app->runningConsoleCommand('route:cache')) {
                $mcpRoutes->register();
            }
        });
    }
}
