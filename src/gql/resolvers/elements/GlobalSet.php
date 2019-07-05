<?php
namespace craft\gql\resolvers\elements;

use craft\elements\GlobalSet as GlobalSetElement;
use craft\helpers\Gql as GqlHelper;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class GlobalSet
 */
class GlobalSet extends BaseElement
{
    /**
     * @inheritdoc
     */
    public static function prepareQuery($source, array $arguments, $fieldName = null)
    {
        $query = GlobalSetElement::find();

        foreach ($arguments as $key => $value) {
            $query->$key($value);
        }

        $pairs = GqlHelper::extractAllowedEntitiesFromToken('read');

        if (!GqlHelper::canQueryGlobalSets()) {
            return [];
        }

        $query->andWhere(['in', 'globalsets.uid', $pairs['globalsets']]);

        return $query;
    }
}
