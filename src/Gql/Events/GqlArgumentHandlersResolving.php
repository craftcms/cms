<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Events;

/**
 * @event GqlArgumentHandlersResolving The event that is triggered when defining GraphQL argument handlers.
 */
class GqlArgumentHandlersResolving
{
    public function __construct(
        /** @var array<string, mixed> */
        public array $handlers,
    ) {}
}
