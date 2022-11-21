<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\resolvers\elements;

use Craft;
use craft\elements\db\ElementQuery;
use craft\elements\Entry as EntryElement;
use craft\gql\base\ElementResolver;
use craft\helpers\Gql as GqlHelper;
use Illuminate\Support\Collection;

/**
 * Class Entry
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class Entry extends ElementResolver
{
    /**
     * @inheritdoc
     */
    public static function prepareQuery(mixed $source, array $arguments, ?string $fieldName = null): mixed
    {
        // If this is the beginning of a resolver chain, start fresh
        if ($source === null) {
            $query = EntryElement::find();
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

        if (!GqlHelper::canQueryEntries()) {
            return Collection::empty();
        }

        $sectionsService = Craft::$app->getSections();
        $sectionIds = array_filter(array_map(function(string $uid) use ($sectionsService) {
            $section = $sectionsService->getSectionByUid($uid);
            return $section->id ?? null;
        }, $pairs['sections']));
        $entryTypeIds = array_filter(array_map(function(string $uid) use ($sectionsService) {
            $entryType = $sectionsService->getEntryTypeByUid($uid);
            return $entryType->id ?? null;
        }, $pairs['entrytypes']));

        $query->andWhere(['in', 'entries.sectionId', $sectionIds]);
        $query->andWhere(['in', 'entries.typeId', $entryTypeIds]);

        return $query;
    }
}
