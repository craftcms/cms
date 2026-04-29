<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use CraftCms\Cms\Asset\Data\Volume;

/**
 * VolumeEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Events\SavingVolume}, {@see \CraftCms\Cms\Asset\Events\VolumeSaved}, {@see \CraftCms\Cms\Asset\Events\DeletingVolume}, {@see \CraftCms\Cms\Asset\Events\ApplyingVolumeDelete}, or {@see \CraftCms\Cms\Asset\Events\VolumeDeleted} instead.
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
