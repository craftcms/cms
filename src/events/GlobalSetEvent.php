<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\elements\GlobalSet;
use yii\base\Event;

/**
 * Global Set event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class GlobalSetEvent extends Event
{
    /**
     * @var GlobalSet The global set model associated with the event.
     */
    public GlobalSet $globalSet;

    /**
     * @var bool Whether the global set is brand new
     */
    public bool $isNew = false;
}
