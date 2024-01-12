<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use craft\log\MonologTarget;

/**
 * Log target event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.7.0
 */
class LogTargetEvent extends Event
{
    public MonologTarget $target;
}
