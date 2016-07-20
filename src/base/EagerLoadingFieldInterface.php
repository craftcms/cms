<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\base;

use craft\app\elements\db\ElementQueryInterface;
use craft\app\records\FieldGroup;

/**
 * EagerLoadingFieldInterface defines the common interface to be implemented by field classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
interface EagerLoadingFieldInterface extends SavableComponentInterface
{
    // Public Methods
    // =========================================================================

    /**
     * Returns an array that maps source-to-target element IDs based on this custom field.
     *
     * This method aids in the eager-loading of elements when performing an element query. The returned array should
     * contain two sub-keys:
     *
     * - `elementType` – indicating the type of sub-elements to eager-load (the element type class handle)
     * - `map` – an array of element ID mappings, where each element is a sub-array with `source` and `target` keys.
     *
     * @param ElementInterface[] $sourceElements An array of the source elements
     *
     * @return array|false The eager-loading element ID mappings, or false if no mappings exist
     */
    public function getEagerLoadingMap($sourceElements);
}
