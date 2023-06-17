<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use yii\base\InvalidConfigException;

/**
 * NestedElementInterface defines the common interface to be implemented by elements that can be
 * nested within other elements via a custom field.
 *
 * @mixin ElementTrait
 * @mixin Component
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
interface NestedElementInterface extends ElementInterface
{
    /**
     * Returns the owner element, if the element has one.
     *
     * @return ElementInterface|null
     * @throws InvalidConfigException if the element is misconfigured
     */
    public function getOwner(): ?ElementInterface;

    /**
     * Returns the field that contains the element.
     *
     * @return ElementContainerFieldInterface|null
     * @throws InvalidConfigException if the element is misconfigured
     */
    public function getField(): ?ElementContainerFieldInterface;
}
