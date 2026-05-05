<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Events;

/**
 * @event GqlSchemaComponentsResolving The event that is triggered when registering GraphQL schema components.
 */
class GqlSchemaComponentsResolving
{
    public function __construct(
        /** @var array<string, mixed> */
        public array $queries,
        /** @var array<string, mixed> */
        public array $mutations,
    ) {}
}
