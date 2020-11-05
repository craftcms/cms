<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\variables;

use Craft;

/**
 * Class ElementIndexes variable.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 3.0.0
 */
class ElementIndexes
{
    /**
     * Returns the element index sources in the custom groupings/order.
     *
     * @param string $elementTypeClass The element type class
     * @param string $context The context
     * @return array
     */
    public function getSources(string $elementTypeClass, string $context = 'index'): array
    {
        Craft::$app->getDeprecator()->log('craft.elementIndexes.getSources()', '`craft.elementIndexes.getSources()` has been deprecated. Use `craft.app.elementIndexes.sources` instead.');

        return Craft::$app->getElementIndexes()->getSources($elementTypeClass, $context);
    }
}
