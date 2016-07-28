<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\web\twig\variables;

use Craft;
use craft\app\base\ElementInterface;

/**
 * Class Elements variable.
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since      3.0
 * @deprecated in 3.0
 */
class Elements
{
    // Public Methods
    // =========================================================================

    /**
     * Constructor
     */
    public function __construct()
    {
        Craft::$app->getDeprecator()->log('craft.elements', 'craft.elements has been deprecated. Use craft.app.elements instead.');
    }

    /**
     * Returns an element type.
     *
     * @param ElementInterface $class
     *
     * @return ElementInterface|null
     */
    public function getElementInstance($class)
    {
        return Craft::$app->getElements()->createElement($class);
    }
}
