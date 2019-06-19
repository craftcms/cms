<?php
namespace craft\gql\resolvers\elements;

use craft\db\Table;
use craft\elements\GlobalSet as GlobalSetElement;
use craft\helpers\Db;
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
    public static function resolve($source, array $arguments, $context, ResolveInfo $resolveInfo)
    {
        $query = GlobalSetElement::find();

        $arguments = self::prepareArguments($arguments);

        foreach ($arguments as $key => $value) {
            $query->$key($value);
        }

        $pairs = GqlHelper::extractAllowedEntitiesFromToken('read');

        if (!empty($pairs['globalsets'])) {
            $query->andWhere(['in', 'globalsets.uid', $pairs['globalsets']]);
        } else {
            return [];
        }

        return $query->all();
    }
}
