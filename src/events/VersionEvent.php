<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\events;

use craft\elements\Entry;

/**
 * Entry event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class VersionEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var Entry The entry version associated with the event.
     */
    public $version;
}
