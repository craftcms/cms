<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

use craft\app\base\VolumeInterface;

/**
 * VolumeEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class VolumeEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var VolumeInterface The volume associated with the event.
     */
    public $volume;

    /**
     * @var boolean Whether the volume is brand new
     */
    public $isNew = false;
}
