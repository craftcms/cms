<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Events;

use CraftCms\Cms\Gql\Data\GqlSchema;

/**
 * @event ExecutedGqlQuery The event that is triggered after a GraphQL query has been executed.
 *
 * @since 6.0.0
 */
class ExecutedGqlQuery
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
        /** @var array<int, string>|null */
        public ?array $cacheTags = null,
        /**
         * The number of seconds the result can be cached for. When GraphQL caching is disabled, it leaves out when
         * entries will go live or expire, since looking those up takes extra queries.
         */
        public ?int $cacheDuration = null,
        public bool $debugMode = false,
    ) {}
}
