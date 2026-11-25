<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Import;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Import\ElementTransformer;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Sections;
use Override;

/**
 * @since 6.0.0
 */
class EntryTransformer extends ElementTransformer
{
    #[Override]
    protected function fieldLayoutFor(ElementInterface $element, array $properties): ?FieldLayout
    {
        $fieldLayout = parent::fieldLayoutFor($element, $properties);

        // a new entry may not have a field layout yet, so fall back to the incoming entry type's
        if ($fieldLayout === null && isset($properties['typeId'])) {
            $fieldLayout = EntryTypes::getEntryTypeById((int) $properties['typeId'])?->getFieldLayout();
        }

        return $fieldLayout;
    }

    /**
     * Normalizes an incoming section value (an ID, UID or handle) to a section ID.
     */
    protected function normalizeSectionId(mixed $value, ElementInterface $element): ?int
    {
        return Sections::resolveSection($value)?->id;
    }

    /**
     * Normalizes an incoming entry-type value (an ID, UID or handle) to a type ID, falling back to the element's current type ID for any other kind of value.
     */
    protected function normalizeTypeId(mixed $value, ElementInterface $element): ?int
    {
        if (! is_int($value) && ! is_string($value)) {
            /** @var Entry $element */
            return $element->getTypeId();
        }

        return EntryTypes::resolveEntryType($value)?->id;
    }
}
