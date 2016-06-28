<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\web\twig\variables;

/**
 * Class ElementIndexes variable.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class ElementIndexes
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the element index sources in the custom groupings/order.
     *
     * @param string $elementTypeClass The element type class
     * @param string $context          The context
     *
     * @return array
     */
    public function getSources($elementTypeClass, $context = 'index')
    {
        return \Craft::$app->getElementIndexes()->getSources($elementTypeClass, $context);
    }
}
