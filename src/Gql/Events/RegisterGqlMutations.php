<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Events;

/**
 * @event RegisterGqlMutations The event that is triggered when registering GraphQL mutations.
 */
class RegisterGqlMutations
{
    public function __construct(
        /** @var array<string, mixed> */
        public array $mutations,
    ) {}
}
