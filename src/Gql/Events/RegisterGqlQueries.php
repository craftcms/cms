<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Events;

/**
 * @event RegisterGqlQueries The event that is triggered when registering GraphQL queries.
 */
class RegisterGqlQueries
{
    public function __construct(
        /** @var array<string, mixed> */
        public array $queries,
    ) {}
}
