<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Events;

/**
 * @event GqlQueriesResolving The event that is triggered when registering GraphQL queries.
 */
class GqlQueriesResolving
{
    public function __construct(
        /** @var array<string, mixed> */
        public array $queries,
    ) {}
}
