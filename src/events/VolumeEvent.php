<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

use craft\app\base\VolumeInterface;

/**
 * Asset Volume event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class VolumeEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var VolumeInterface The asset Volume associated with the event.
     */
    public $volume;

    /**
     * @var boolean Whether the Volume is brand new
     */
    public $isNew;
}
