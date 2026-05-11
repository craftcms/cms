<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Events;

/**
 * @event GqlEagerLoadableFieldsResolving The event that is triggered when defining additional eager-loadable GraphQL fields.
 */
class GqlEagerLoadableFieldsResolving
{
    public function __construct(
        /** @var array<string, mixed> */
        public array $fieldList,
    ) {}
}
