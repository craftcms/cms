<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql;

use CraftCms\Cms\Auth\OAuthServer\OAuthResource;
use CraftCms\Cms\Auth\OAuthServer\OAuthResources;
use CraftCms\Cms\Auth\PersonalAccessTokens\PersonalAccessTokens;
use CraftCms\Cms\Auth\PersonalAccessTokens\ScopeGroup;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Gql\Commands\CreateTokenCommand;
use CraftCms\Cms\Gql\Commands\DumpSchemaCommand;
use CraftCms\Cms\Gql\Commands\ListSchemasCommand;
use CraftCms\Cms\Gql\Commands\PrintSchemaCommand;
use Illuminate\Support\ServiceProvider;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class GqlServiceProvider extends ServiceProvider
{
    public function boot(PersonalAccessTokens $personalAccessTokens, OAuthResources $oauthResources): void
    {
        $this->commands([
            ListSchemasCommand::class,
            PrintSchemaCommand::class,
            DumpSchemaCommand::class,
            CreateTokenCommand::class,
        ]);

        $scopes = fn (): array => [
            Gql::SCOPE_READ => t('Query content you have access to.'),
            Gql::SCOPE_WRITE => t('Create, edit, and delete content you have access to.'),
        ];

        // Users can create personal access tokens for the GraphQL API while it’s enabled
        $personalAccessTokens->registerScopeGroup('graphql', fn (): ?ScopeGroup => Cms::config()->enableGql ? new ScopeGroup(
            handle: 'graphql',
            label: t('GraphQL API'),
            scopes: $scopes(),
        ) : null);

        // …and authorize OAuth clients to access it on their behalf
        $oauthResources->register('graphql', function () use ($scopes): ?OAuthResource {
            if (! Cms::config()->enableGql) {
                return null;
            }

            $actionTrigger = trim(Cms::config()->actionTrigger, '/');

            return new OAuthResource(
                handle: 'graphql',
                levels: [
                    Gql::SCOPE_READ => ['label' => t('Read only'), 'description' => $scopes()[Gql::SCOPE_READ]],
                    Gql::SCOPE_WRITE => ['label' => t('Read and write'), 'description' => $scopes()[Gql::SCOPE_WRITE]],
                ],
                resourceUrl: url("$actionTrigger/graphql/api"),
                authorizationRoute: ltrim("$actionTrigger/graphql", '/'),
                // Build URLs on the app’s root rather than with `action_url()`, which adds the control panel trigger on
                // control panel requests and in headless mode, and the request’s site and preview tokens
                baseUrl: url('/'),
                routeName: 'craft.graphql.oauth',
            );
        });

        if (Cms::config()->enableGql && ! $this->app->routesAreCached()) {
            $oauthResources->routes('graphql');
        }
    }
}
