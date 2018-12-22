<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\VolumeInterface;
use yii\base\Event;

/**
 * VolumeEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class VolumeEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var VolumeInterface|null The volume associated with the event.
     */
    public $volume;

    /**
     * @var bool Whether the volume is brand new
     */
    public $isNew = false;
}
