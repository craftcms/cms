<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

use craft\app\elements\GlobalSet;

/**
 * Global Set content event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class GlobalSetContentEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var GlobalSet The global set model associated with the event.
     */
    public $globalSet;
}
