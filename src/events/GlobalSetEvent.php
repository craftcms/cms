<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

use craft\app\elements\GlobalSet;

/**
 * Global Set event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class GlobalSetEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var GlobalSet The global set model associated with the event.
     */
    public $globalSet;

    /**
     * @var boolean Whether the global set is brand new
     */
    public $isNew = false;
}
