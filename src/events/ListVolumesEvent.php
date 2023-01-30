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
 * ListVolumesEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
class ListVolumesEvent extends Event
{
    /**
     * @var Volume[] The volumes to be listed.
     */
    public array $volumes = [];
}
