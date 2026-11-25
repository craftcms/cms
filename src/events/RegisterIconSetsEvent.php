<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use craft\icons\IconSet;

/**
 * RegisterIconSetsEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.0
 */
class RegisterIconSetsEvent extends Event
{
    /**
     * @var IconSet[] The registered custom icon sets
     */
    public array $iconSets = [];
}
