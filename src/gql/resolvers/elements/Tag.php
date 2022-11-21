<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\resolvers\elements;

use Craft;
use craft\elements\db\ElementQuery;
use craft\elements\Tag as TagElement;
use craft\gql\base\ElementResolver;
use craft\helpers\Gql as GqlHelper;
use Illuminate\Support\Collection;

/**
 * Class Tag
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class Tag extends ElementResolver
{
    /**
     * @inheritdoc
     */
    public static function prepareQuery(mixed $source, array $arguments, ?string $fieldName = null): mixed
    {
        // If this is the beginning of a resolver chain, start fresh
        if ($source === null) {
            $query = TagElement::find();
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

        if (!GqlHelper::canQueryTags()) {
            return Collection::empty();
        }

        $tagsService = Craft::$app->getTags();
        $tagGroupIds = array_filter(array_map(function(string $uid) use ($tagsService) {
            $tagGroup = $tagsService->getTagGroupByUid($uid);
            return $tagGroup->id ?? null;
        }, $pairs['taggroups']));

        $query->andWhere(['in', 'tags.groupId', $tagGroupIds]);

        return $query;
    }
}
