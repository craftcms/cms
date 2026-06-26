<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Transformers;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Sections;

class EntryTransformer extends ElementTransformer
{
    protected function normalizeSectionId(mixed $value, ElementInterface $element): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value)) {
            // $section = Sections::getSectionById($value);
            return $value;
        }

        if (is_string($value)) {
            $section = Sections::getSectionByHandle($value);
            if ($section) {
                return $section->id;
            }
        }

        return null;
    }

    protected function normalizeTypeId(mixed $value, ElementInterface $element): ?int
    {
        if ($value === null) {
            return $element->typeId;
        }

        if (is_int($value)) {
            // $section = Sections::getSectionById($value);
            return $value;
        }

        if (is_string($value)) {
            $type = EntryTypes::getEntryTypeByHandle($value);
            if ($type) {
                return $type->id;
            }
        }

        return $element->getTypeId();
    }
}
