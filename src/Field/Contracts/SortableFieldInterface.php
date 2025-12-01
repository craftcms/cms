<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Contracts;

/**
 * SortableFieldInterface defines the common interface to be implemented by field classes that can be available as
 * sort options on element indexes.
 */
interface SortableFieldInterface
{
    /**
     * Returns the field’s sort option definition.
     *
     * This should return an array with the following keys:
     *
     * - `label` – The sort option label
     * - `orderBy` – An array or comma-delimited string of columns to order the query by
     * - `attribute` – The table attribute name that this option is associated with
     *   (required if `orderBy` is an array or more than one column name)
     */
    public function getSortOption(): array;
}
