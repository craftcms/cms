<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * SortableFieldInterface defines the common interface to be implemented by field classes that can be available as
 * sort options on element indexes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2.0
 */
interface SortableFieldInterface
{
    /**
     * Returns the field’s sort option configuration.
     *
     * This should return an array with the following keys:
     *
     * - `label` – The sort option label
     * - `orderBy` – An array or comma-delimited string of columns to order the query by
     * - `attribute` _(optional)_ – The table attribute name that this option is associated with
     *   (required if `orderBy` is an array or more than one column name)
     *
     * @return array
     */
    public function getSortOption(): array;
}
