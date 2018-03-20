<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\elements\Entry;
use yii\base\Event;

/**
 * Entry event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class VersionEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var Entry|null The entry version associated with the event.
     */
    public $version;
}
