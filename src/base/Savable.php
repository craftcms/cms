<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\base;

/**
 * Storable is the interface that should be implemented by classes who want to support customizable representation of their instances
 * when getting saved to the database.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
interface Savable
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the object’s savable value.
     *
     * @return mixed The savable value
     */
    public function getSavableValue();
}
