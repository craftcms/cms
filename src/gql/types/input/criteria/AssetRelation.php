<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\input\criteria;

use craft\gql\arguments\elements\Asset as AssetArguments;
use craft\gql\arguments\RelationCriteria;
use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\InputObjectType;

/**
 * Class AssetRelation
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.4.0
 */
class AssetRelation extends InputObjectType
{
    /**
     * @return mixed
     */
    public static function getType(): mixed
    {
        $typeName = 'AssetRelationCriteriaInput';

        return GqlEntityRegistry::getOrCreate($typeName, fn() => new InputObjectType([
            'name' => $typeName,
            'fields' => fn() => AssetArguments::getArguments() + RelationCriteria::getArguments(),
        ]));
    }
}
