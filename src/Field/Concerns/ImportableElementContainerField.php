<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Concerns;

use CraftCms\Cms\Field\Contracts\ImportableElementContainerFieldInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;

/**
 * ImportableElementContainerFieldTrait provides a base implementation for {@see ImportableElementContainerFieldInterface}.
 */
trait ImportableElementContainerField
{
    /**
     * @see ImportableElementContainerFieldInterface::normalizeNestedEntryForImport()
     */
    public function normalizeNestedEntryForImport(array $dataItem, FieldLayout $fieldLayout): array
    {
        $fields = $dataItem['fields'] ?? [];

        foreach ($fields as $handle => $value) {
            if (! is_array($value)) {
                continue;
            }
            $field = $fieldLayout->getFieldByHandle($handle);

            // if we don't have a field, or it's not an importable nested elements type field,
            // we don't have to worry about extra normalization, so carry on
            if (! $field instanceof ImportableElementContainerFieldInterface) {
                continue;
            }

            $dataItem['fields'][$handle] = $field->normalizeValueForImport($value);
        }

        return $dataItem;
    }
}
