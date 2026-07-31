<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Transformers;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Sections;

class EntryTransformer extends ElementTransformer
{
    /**
     * Normalizes an incoming section value (numeric id or handle string) to a section ID.
     */
    protected function normalizeSectionId(mixed $value, ElementInterface $element): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value) || is_numeric($value)) {
            // $section = Sections::getSectionById($value);
            return (int) $value;
        }

        if (is_string($value)) {
            $section = Sections::getSectionByHandle($value);
            if ($section) {
                return $section->id;
            }
        }

        return null;
    }

    /**
     * Normalizes an incoming entry-type value (numeric id or handle string) to a type ID, falling back to the element's current type ID.
     */
    protected function normalizeTypeId(mixed $value, ElementInterface $element): ?int
    {
        if ($value === null) {
            /** @var $element Entry */
            return $element->getTypeId();
        }

        if (is_int($value)) {
            // $section = Sections::getSectionById($value);
            return $value;
        }

        if (is_string($value)) {
            $type = EntryTypes::getEntryTypeByHandle($value);

            return $type?->id;
        }

        /** @var $element Entry */
        return $element->getTypeId();
    }
}
