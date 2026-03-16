<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Events;

/**
 * @event RegisterGqlArgumentHandlers The event that is triggered when defining GraphQL argument handlers.
 */
class RegisterGqlArgumentHandlers
{
    public function __construct(
        /** @var array<string, mixed> */
        public array $handlers,
    ) {}
}
