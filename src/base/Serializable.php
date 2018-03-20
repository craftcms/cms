<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * Serializable is the interface that should be implemented by classes who want to support customizable representation of their instances
 * when getting stored somewhere.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
interface Serializable
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the object’s serialized value.
     *
     * @return mixed The serialized value
     */
    public function serialize();
}
