<?php
namespace craft\gql\resolvers\elements;

use craft\db\Table;
use craft\elements\Asset as AssetElement;
use craft\helpers\Db;
use craft\helpers\Gql as GqlHelper;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class Asset
 */
class Asset extends BaseElement
{
    /**
     * @inheritdoc
     */
    public static function resolve($source, array $arguments, $context, ResolveInfo $resolveInfo)
    {
        // If this is the begining of a resolver chain, start fresh
        if ($source === null) {
            $query = AssetElement::find();
        // If not, get the prepared element query
        } else {
            $fieldName = $resolveInfo->fieldName;
            $query = $source->$fieldName;
        }

        $arguments = self::prepareArguments($arguments);

        foreach ($arguments as $key => $value) {
            $query->$key($value);
        }

        $pairs = GqlHelper::extractAllowedEntitiesFromToken('read');

        if (!empty($pairs['volumes'])) {
            $allowedIds = Db::idsByUids(Table::VOLUMES, $pairs['volumes']);
            $query->volumeId = $query->volumeId ? array_intersect($allowedIds, (array)$query->volumeId) : $allowedIds;
        } else {
            return [];
        }

        return $query->all();
    }
}
