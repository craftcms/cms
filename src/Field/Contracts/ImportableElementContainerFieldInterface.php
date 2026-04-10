<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Contracts;

use craft\base\ElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;

/**
 * ImportableElementContainerFieldInterface defines the common interface to be implemented by field classes
 * that contain nested elements and wish to support importing content via the import mechanism.
 */
interface ImportableElementContainerFieldInterface extends FieldInterface
{
    /**
     * Normalizes field's value for import.
     */
    public function normalizeValueForImport(mixed $value, ?ElementInterface $owner = null): array;

    /**
     * Normalize the nested entry data for import, applying the specified field layout configuration.
     *
     * @param  array  $dataItem  The data item to be normalized.
     * @param  FieldLayout  $fieldLayout  The field layout to apply to the data item.
     * @return array The normalized data item.
     */
    public function normalizeNestedEntryForImport(array $dataItem, FieldLayout $fieldLayout, ?ElementInterface $owner = null): array;
}
