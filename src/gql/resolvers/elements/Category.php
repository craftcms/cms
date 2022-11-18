<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\resolvers\elements;

use Craft;
use craft\elements\Category as CategoryElement;
use craft\elements\db\ElementQuery;
use craft\gql\base\ElementResolver;
use craft\helpers\Gql as GqlHelper;
use Illuminate\Support\Collection;

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
    public static function prepareQuery(mixed $source, array $arguments, ?string $fieldName = null): mixed
    {
        // If this is the beginning of a resolver chain, start fresh
        if ($source === null) {
            $query = CategoryElement::find();
        // If not, get the prepared element query
        } else {
            $query = $source->$fieldName;
        }

        // If it's preloaded, it's preloaded.
        if (!$query instanceof ElementQuery) {
            return $query;
        }

        foreach ($arguments as $key => $value) {
            $query->$key($value);
        }

        $pairs = GqlHelper::extractAllowedEntitiesFromSchema('read');

        if (!GqlHelper::canQueryCategories()) {
            return Collection::empty();
        }

        $categoriesService = Craft::$app->getCategories();
        $groupIds = array_filter(array_map(function(string $uid) use ($categoriesService) {
            $group = $categoriesService->getGroupByUid($uid);
            return $group->id ?? null;
        }, $pairs['categorygroups']));

        $query->andWhere(['in', 'categories.groupId', $groupIds]);

        return $query;
    }
}
