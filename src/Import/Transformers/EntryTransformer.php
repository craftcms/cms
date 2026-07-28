<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Transformers;

use CraftCms\Cms\Element\Contracts\ElementInterface;
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
}
