<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\resolvers\elements;

use craft\db\Table;
use craft\elements\Category as CategoryElement;
use craft\gql\base\ElementResolver;
use craft\helpers\Db;
use craft\helpers\Gql as GqlHelper;

/**
 * Class Category
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class Category extends ElementResolver
{
    /**
     * @inheritdoc
     */
    public static function prepareQuery($source, array $arguments, $fieldName = null)
    {
        // If this is the beginning of a resolver chain, start fresh
        if ($source === null) {
            $query = CategoryElement::find();
            // If not, get the prepared element query
        } else {
            $query = $source->$fieldName;
        }

        // If it's preloaded, it's preloaded.
        if (is_array($query)) {
            return $query;
        }

        foreach ($arguments as $key => $value) {
            $query->$key($value);
        }

        $pairs = GqlHelper::extractAllowedEntitiesFromSchema('read');

        if (!GqlHelper::canQueryCategories()) {
            return [];
        }

        $query->andWhere(['in', 'categories.groupId', array_values(Db::idsByUids(Table::CATEGORYGROUPS, $pairs['categorygroups']))]);

        return $query;
    }
}
