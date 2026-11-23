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
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Url;
use Illuminate\Support\ServiceProvider;

use function CraftCms\Cms\action_url;
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
            // Action URLs carry over the request’s site and preview tokens, which must stay out of public discovery
            // metadata and the resource’s identifier
            $resourceUrl = Url::stripQueryString(action_url('graphql/api'));

            // The OAuth routes live at the app’s root, while action URLs include the control panel trigger on control
            // panel requests and in headless mode
            $baseUrl = url('/');

            // Every host shares one authorization server, whose clients, keys, and tokens work on all of them, so its
            // issuer comes from the configured URL rather than the request’s host. The API itself stays on each host.
            // In headless mode, and without a control panel trigger, `APP_URL` may be a front end that Craft doesn’t
            // serve, so the control panel’s URL is used. Only the scheme and host are taken, so a site path in
            // `APP_URL` stays out of the OAuth URLs.
            $configuredUrl = Cms::config()->headlessMode || trim((string) Cms::config()->cpTrigger, '/') === ''
                ? (string) Env::parse(Cms::config()->baseCpUrl)
                : (string) config('app.url');

            // …unless it’s unset, or Laravel’s default
            if ($configuredUrl !== 'http://localhost' && parse_url($configuredUrl, PHP_URL_HOST)) {
                $baseUrl = Url::hostInfo($configuredUrl).parse_url($baseUrl, PHP_URL_PATH);
            }

            return new OAuthResource(
                handle: 'graphql',
                levels: [
                    Gql::SCOPE_READ => ['label' => t('Read only'), 'description' => $scopes()[Gql::SCOPE_READ]],
                    Gql::SCOPE_WRITE => ['label' => t('Read and write'), 'description' => $scopes()[Gql::SCOPE_WRITE]],
                ],
                resourceUrl: $resourceUrl,
                authorizationRoute: ltrim("$actionTrigger/graphql", '/'),
                baseUrl: $baseUrl,
                routeName: 'craft.graphql.oauth',
            );
        });

        if (Cms::config()->enableGql && ! $this->app->routesAreCached()) {
            $oauthResources->routes('graphql');
        }
    }
}
