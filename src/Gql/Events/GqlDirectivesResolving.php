<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Events;

/**
 * @event GqlDirectivesResolving The event that is triggered when registering GraphQL directives.
 */
class GqlDirectivesResolving
{
    public function __construct(
        /** @var array<int, class-string> */
        public array $directives,
    ) {}
}
