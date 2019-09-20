<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * RegisterElementActionsEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class RegisterElementActionsEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var string The selected source’s key
     */
    public $source;

    /**
     * @var array List of registered actions for the element type.
     */
    public $actions = [];
}
