<?php
/**
 * @link      http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license
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
