<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\input\criteria;

use craft\gql\arguments\elements\User as UserArguments;
use craft\gql\arguments\RelationCriteria;
use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\InputObjectType;

/**
 * Class UserRelation
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.4.0
 */
class UserRelation extends InputObjectType
{
    /**
     * @return mixed
     */
    public static function getType(): mixed
    {
        $typeName = 'UserRelationCriteriaInput';

        return GqlEntityRegistry::getOrCreate($typeName, fn() => new InputObjectType([
            'name' => $typeName,
            'fields' => fn() => UserArguments::getArguments() + RelationCriteria::getArguments(),
        ]));
    }
}
