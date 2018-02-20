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
 * Global Set content event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class GlobalSetContentEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var GlobalSet|null The global set model associated with the event.
     */
    public $globalSet;
}
