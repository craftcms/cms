<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * DefineValueEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.7.0
 */
class DefineValueEvent extends Event
{
    /**
     * @var mixed The value being defined by the event
     */
    public mixed $value = null;
}
