<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Events;

/**
 * @event DefineGqlTypeFields The event that is triggered when defining GraphQL type fields.
 */
class DefineGqlTypeFields
{
    public function __construct(
        /** @var array<string, mixed> */
        public array $fields,
        public string $typeName,
    ) {}
}
