<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Resolvers\Elements;

use BadMethodCallException;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Gql\Resolvers\ElementResolver;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sections;
use Illuminate\Database\Query\Builder;

class Entry extends ElementResolver
{
    public static function prepareQuery(mixed $source, array $arguments, ?string $fieldName = null): mixed
    {
        // If this is the beginning of a resolver chain, start fresh
        if ($source === null) {
            $query = EntryElement::find();
            $pairs = GqlHelper::extractAllowedEntitiesFromSchema('read');

            if (! isset($pairs['sections']) && ! isset($pairs['nestedentryfields'])) {
                return ElementCollection::empty();
            }

            $query->where(function (Builder $query) use ($pairs) {
                if (isset($pairs['sections'])) {
                    $sectionIds = collect($pairs['sections'])
                        ->map(fn (string $uid) => Sections::getSectionByUid($uid)?->id)
                        ->filter()
                        ->all();

                    if (! empty($sectionIds)) {
                        $query->orWhereIn('entries.sectionId', $sectionIds);
                    }
                }

                if (isset($pairs['nestedentryfields'])) {
                    $types = Fields::getNestedEntryFieldTypes()->flip();
                    $fieldIds = collect($pairs['nestedentryfields'])
                        ->map(function (string $uid) use ($types) {
                            $field = Fields::getFieldByUid($uid);

                            return $field && $types->has($field::class) ? $field->id : null;
                        })
                        ->filter()
                        ->all();

                    if (! empty($fieldIds)) {
                        $query->orWhereIn('entries.fieldId', $fieldIds);
                    }
                }
            });
            // If not, get the prepared element query
        } else {
            $query = $source->$fieldName;
        }

        // If it's preloaded, it's preloaded.
        if (! $query instanceof ElementQueryInterface) {
            return $query;
        }

        foreach ($arguments as $key => $value) {
            try {
                $query->$key($value);
            } catch (BadMethodCallException $e) {
                if ($value !== null) {
                    throw $e;
                }
            }
        }

        return $query;
    }
}
