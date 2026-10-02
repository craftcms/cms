<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Events;

/**
 * @event GqlValidationRulesResolving The event that is triggered when defining GraphQL validation rules.
 *
 * @since 6.0.0
 */
class GqlValidationRulesResolving
{
    public function __construct(
        /** @var array<class-string, mixed> */
        public array $validationRules,
        public bool $debug,
    ) {}
}
