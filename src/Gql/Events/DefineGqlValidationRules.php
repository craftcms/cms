<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Events;

/**
 * @event DefineGqlValidationRules The event that is triggered when defining GraphQL validation rules.
 */
class DefineGqlValidationRules
{
    public function __construct(
        /** @var array<class-string, mixed> */
        public array $validationRules,
        public bool $debug,
    ) {}
}
