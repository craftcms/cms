<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * DefineConsoleActionsEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2.0
 */
class DefineConsoleActionsEvent extends Event
{
    /**
     * @var array The additional actions that should be available to console controllers.
     *
     * See [[\craft\console\Controller::defineActions()]] for details on what to set on this.
     */
    public $actions = [];
}
