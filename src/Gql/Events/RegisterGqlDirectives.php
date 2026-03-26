<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Events;

/**
 * @event RegisterGqlDirectives The event that is triggered when registering GraphQL directives.
 */
class RegisterGqlDirectives
{
    public function __construct(
        /** @var array<int, class-string> */
        public array $directives,
    ) {}
}
