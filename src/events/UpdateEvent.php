<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

/**
 * Update event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class UpdateEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var string The type of update ("manual" or "auto")
     */
    public $type;

    /**
     * @var string The handle of whatever initiated the update ("craft" or a plugin’s handle)
     */
    public $handle;
}
