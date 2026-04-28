<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Events;

/**
 * @event RegisterGqlTypes The event that is triggered when registering GraphQL types.
 */
class RegisterGqlTypes
{
    public function __construct(
        /** @var array<int, class-string> */
        public array $types,
    ) {}
}
