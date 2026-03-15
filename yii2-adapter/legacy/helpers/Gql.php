<?php

declare(strict_types=1);

namespace craft\helpers;

use craft\models\GqlSchema;
use CraftCms\Cms\Gql\GqlHelper;
use Deprecated;

/**
 * @deprecated 6.0.0 use {@see GqlHelper} instead.
 */
class Gql extends GqlHelper
{
    /**
     * @param  GqlSchema|null  $schema  The GraphQL schema. If none is provided, the active schema will be used.
     */
    #[Deprecated(message: 'in 6.0.0')]
    public static function canMutateTags(?GqlSchema $schema = null): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema('edit', $schema);

        return isset($allowedEntities['taggroups']);
    }

    /**
     * @param  GqlSchema|null  $schema  The GraphQL schema. If none is provided, the active schema will be used.
     */
    #[Deprecated(message: 'in 6.0.0')]
    public static function canMutateGlobalSets(?GqlSchema $schema = null): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema('edit', $schema);

        return isset($allowedEntities['globalsets']);
    }

    /**
     * @param  GqlSchema|null  $schema  The GraphQL schema. If none is provided, the active schema will be used.
     */
    #[Deprecated(message: 'in 6.0.0')]
    public static function canMutateCategories(?GqlSchema $schema = null): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema('edit', $schema);

        return isset($allowedEntities['categorygroups']);
    }

    /**
     * @param  GqlSchema|null  $schema  The GraphQL schema. If none is provided, the active schema will be used.
     */
    #[Deprecated(message: 'in 6.0.0')]
    public static function canQueryCategories(?GqlSchema $schema = null): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema('read', $schema);

        return isset($allowedEntities['categorygroups']);
    }

    /**
     * @param  GqlSchema|null  $schema  The GraphQL schema. If none is provided, the active schema will be used.
     */
    #[Deprecated(message: 'in 6.0.0')]
    public static function canQueryTags(?GqlSchema $schema = null): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema('read', $schema);

        return isset($allowedEntities['taggroups']);
    }

    /**
     * @param  GqlSchema|null  $schema  The GraphQL schema. If none is provided, the active schema will be used.
     */
    #[Deprecated(message: 'in 6.0.0')]
    public static function canQueryGlobalSets(?GqlSchema $schema = null): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema('read', $schema);

        return isset($allowedEntities['globalsets']);
    }
}
