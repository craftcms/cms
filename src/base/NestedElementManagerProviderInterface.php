<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\elements\NestedElementManager;

/**
 * NestedElementManagerProviderInterface defines the common interface to be implemented by elements that provide
 * one or more nested element providers for the elements that can be owned by them.
 *
 *
 * @mixin ElementTrait
 * @mixin Component
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.x
 */
interface NestedElementManagerProviderInterface extends ElementInterface
{
    /**
     * Returns the nested element manager by the attribute name.
     *
     * @param string $attribute The attribute name
     * @return ?NestedElementManager
     */
    public function getNestedElementManager(string $attribute): ?NestedElementManager;
}
