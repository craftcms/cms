<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\events;

use craft\base\VolumeInterface;

/**
 * VolumeEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class VolumeEvent extends \yii\base\Event
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
