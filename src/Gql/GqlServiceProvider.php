<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql;

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
    public function boot(PersonalAccessTokens $personalAccessTokens): void
    {
        $this->commands([
            ListSchemasCommand::class,
            PrintSchemaCommand::class,
            DumpSchemaCommand::class,
            CreateTokenCommand::class,
        ]);

        // Users can create personal access tokens for the GraphQL API while it’s enabled
        $personalAccessTokens->registerScopeGroup('graphql', fn (): ?ScopeGroup => Cms::config()->enableGql ? new ScopeGroup(
            handle: 'graphql',
            label: t('GraphQL API'),
            scopes: [
                Gql::SCOPE_READ => t('Query content you have access to.'),
                Gql::SCOPE_WRITE => t('Create, edit, and delete content you have access to.'),
            ],
        ) : null);
    }
}
