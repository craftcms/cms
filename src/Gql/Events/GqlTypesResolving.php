<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Events;

/**
 * @event GqlTypesResolving The event that is triggered when registering GraphQL types.
 */
class GqlTypesResolving
{
    public function __construct(
        /** @var array<int, class-string> */
        public array $types,
    ) {}
}
