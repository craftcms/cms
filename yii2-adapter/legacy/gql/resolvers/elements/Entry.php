<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\resolvers\elements;

use craft\gql\base\ElementResolver;
use craft\helpers\Gql as GqlHelper;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Support\Facades\Sections;
use Illuminate\Database\Query\Builder;
use yii\base\UnknownMethodException;

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
            $pairs = GqlHelper::extractAllowedEntitiesFromSchema('read');

            if (!isset($pairs['sections']) && !isset($pairs['nestedentryfields'])) {
                return ElementCollection::empty();
            }

            $query->where(function(Builder $query) use ($pairs) {
                if (isset($pairs['sections'])) {
                    $sectionIds = array_filter(array_map(
                        fn(string $uid) => Sections::getSectionByUid($uid)?->id,
                        $pairs['sections'],
                    ));
                    if (!empty($sectionIds)) {
                        $query->orWhereIn('entries.sectionId', $sectionIds);
                    }
                }

                if (isset($pairs['nestedentryfields'])) {
                    $fieldsService = app(Fields::class);
                    $types = $fieldsService->getNestedEntryFieldTypes()->flip();
                    $fieldIds = array_filter(array_map(function(string $uid) use ($fieldsService, $types) {
                        $field = $fieldsService->getFieldByUid($uid);
                        return $field && isset($types[$field::class]) ? $field->id : null;
                    }, $pairs['nestedentryfields']));
                    if (!empty($fieldIds)) {
                        $query->orWhereIn('entries.fieldId', $fieldIds);
                    }
                }
            });
        // If not, get the prepared element query
        } else {
            $query = $source->$fieldName;
        }

        // If it's preloaded, it's preloaded.
        if (!$query instanceof ElementQueryInterface) {
            return $query;
        }

        foreach ($arguments as $key => $value) {
            try {
                $query->$key($value);
            } catch (UnknownMethodException $e) {
                if ($value !== null) {
                    throw $e;
                }
            }
        }

        return $query;
    }
}
