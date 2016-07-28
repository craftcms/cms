<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\web\twig\variables;

use Craft;

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
        return Craft::$app->getElementIndexes()->getSources($elementTypeClass, $context);
    }
}
