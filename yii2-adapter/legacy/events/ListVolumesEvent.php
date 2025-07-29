<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use craft\models\Volume;

/**
 * ListVolumesEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 * @deprecated in 6.0.0. [[\CraftCms\Cms\Utility\Events\ListVolumes]] should be used instead.
 */
class ListVolumesEvent extends Event
{
    /**
     * @var Volume[] The volumes to be listed.
     */
    public array $volumes = [];
}
