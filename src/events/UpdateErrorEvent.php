<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

/**
 * Update error event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class UpdateErrorEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var string The handle of whatever initiated the update (Craft or a plugin)
     */
    public $handle;
}
