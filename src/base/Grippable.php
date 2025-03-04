<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * Grippable defines the common interface to be implemented by components that
 * can be identified by a handle.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.3.0
 */
interface Grippable
{
    /**
     * Returns the handle of the component.
     *
     * @return string|null
     */
    public function getHandle(): ?string;
}
