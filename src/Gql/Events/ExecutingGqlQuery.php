<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Events;

use craft\models\GqlSchema;

/**
 * @event ExecutingGqlQuery The event that is triggered before a GraphQL query is executed.
 */
class ExecutingGqlQuery
{
    public function __construct(
        public GqlSchema $schema,
        public string $query,
        /** @var array<string, mixed>|null */
        public ?array $variables = null,
        public ?string $operationName = null,
        /** @var array<string, mixed> */
        public array $context = [],
        public mixed $rootValue = null,
        /** @var array<string, mixed>|null */
        public ?array $result = null,
        public bool $debugMode = false,
    ) {}
}
