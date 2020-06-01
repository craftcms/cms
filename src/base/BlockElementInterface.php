<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use yii\base\InvalidConfigException;

/**
 * BlockElementInterface defines the common interface to be implemented by “block element” classes.
 *
 * Block elements are elements that are owned by a parent element.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2.0
 */
interface BlockElementInterface
{
    /**
     * Returns the owner element.
     *
     * @return ElementInterface
     * @throws InvalidConfigException if the element is unable to determine its owner
     */
    public function getOwner(): ElementInterface;
}
