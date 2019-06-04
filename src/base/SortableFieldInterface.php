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
 * @since 3.2
 */
interface SortableFieldInterface
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the sort option array that should be included in the element’s
     * [[\craft\base\ElementInterface::sortOptions()|sortOptions()]] response.
     *
     * @return array
     */
    public function getSortOption(): array;
}
