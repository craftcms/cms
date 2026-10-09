<?php

declare(strict_types=1);

namespace craft\helpers;

use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\GqlHelper;

/**
 * @deprecated 6.0.0 use {@see GqlHelper} instead.
 */
class Gql extends GqlHelper
{
    /**
     * @deprecated in 6.0.0
     *
     * @param  GqlSchema|null  $schema  The GraphQL schema. If none is provided, the active schema will be used.
     */
    public static function canMutateTags(?GqlSchema $schema = null): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema('edit', $schema);

        return isset($allowedEntities['taggroups']);
    }

    /**
     * @deprecated in 6.0.0
     *
     * @param  GqlSchema|null  $schema  The GraphQL schema. If none is provided, the active schema will be used.
     */
    public static function canMutateGlobalSets(?GqlSchema $schema = null): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema('edit', $schema);

        return isset($allowedEntities['globalsets']);
    }

    /**
     * @deprecated in 6.0.0
     *
     * @param  GqlSchema|null  $schema  The GraphQL schema. If none is provided, the active schema will be used.
     */
    public static function canMutateCategories(?GqlSchema $schema = null): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema('edit', $schema);

        return isset($allowedEntities['categorygroups']);
    }

    /**
     * @deprecated in 6.0.0
     *
     * @param  GqlSchema|null  $schema  The GraphQL schema. If none is provided, the active schema will be used.
     */
    public static function canQueryCategories(?GqlSchema $schema = null): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema('read', $schema);

        return isset($allowedEntities['categorygroups']);
    }

    /**
     * @deprecated in 6.0.0
     *
     * @param  GqlSchema|null  $schema  The GraphQL schema. If none is provided, the active schema will be used.
     */
    public static function canQueryTags(?GqlSchema $schema = null): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema('read', $schema);

        return isset($allowedEntities['taggroups']);
    }

    /**
     * @deprecated in 6.0.0
     *
     * @param  GqlSchema|null  $schema  The GraphQL schema. If none is provided, the active schema will be used.
     */
    public static function canQueryGlobalSets(?GqlSchema $schema = null): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema('read', $schema);

        return isset($allowedEntities['globalsets']);
    }
}
