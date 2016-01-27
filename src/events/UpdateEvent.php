<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
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
     * @var string The type of update (manual or auto).
     */
    public $type;
}
