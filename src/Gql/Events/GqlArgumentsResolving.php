<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Events;

use CraftCms\Cms\Gql\Arguments\Arguments;

/**
 * @event GqlArgumentsResolving The event that is triggered when defining GraphQL arguments.
 */
class GqlArgumentsResolving
{
    public function __construct(
        /** @var array<string, mixed> */
        public array $arguments,
        /** @var class-string<Arguments> */
        public string $argumentClass,
    ) {}
}
