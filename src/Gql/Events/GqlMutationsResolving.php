<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Events;

/**
 * @event GqlMutationsResolving The event that is triggered when registering GraphQL mutations.
 */
class GqlMutationsResolving
{
    public function __construct(
        /** @var array<string, mixed> */
        public array $mutations,
    ) {}
}
