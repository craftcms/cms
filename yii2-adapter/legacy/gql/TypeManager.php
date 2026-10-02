<?php

declare(strict_types=1);

namespace craft\gql;

use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Facades\Gql;
use Deprecated;

/**
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Gql\TypeManager} instead.
 */
class TypeManager extends \CraftCms\Cms\Gql\TypeManager
{
    public const string EVENT_DEFINE_GQL_TYPE_FIELDS = 'defineGqlTypeFields';

    /**
     * Prepare field definitions for a GraphQL type by giving plugins a chance to modify them.
     */
    #[Deprecated(message: 'in 4.0.0. Use [[craft\services\Gql::prepareFieldDefinitions()|`\\CraftCms\\Cms\\Support\\Facades\\Gql::prepareFieldDefinitions()`]] instead.')]
    public static function prepareFieldDefinitions(array $fields, string $typeName): array
    {
        Deprecator::log('TypeManager::prepareFieldDefinitions()', '`TypeManager::prepareFieldDefinitions()` has been deprecated. Use `craft\services\Gql::prepareFieldDefinition()` instead.');

        return Gql::prepareFieldDefinitions($fields, $typeName);
    }

    /**
     * Flush all prepared field definitions.
     */
    #[Deprecated(message: 'in 4.0.0. `craft\services\Gql::flushCaches()` should be used instead.')]
    public static function flush(): void
    {
        Deprecator::log('TypeManager::flush()', '`TypeManager::flush()` has been deprecated and has no effect.');
    }
}
