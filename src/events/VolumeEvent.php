<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\models\Volume;
use yii\base\Event;

/**
 * VolumeEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class VolumeEvent extends Event
{
    /**
     * @var Volume The volume associated with the event.
     */
    public Volume $volume;

    /**
     * @var bool Whether the volume is brand new
     */
    public bool $isNew = false;
}
