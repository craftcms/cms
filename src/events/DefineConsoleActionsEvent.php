<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\ElementInterface;
use yii\base\Event;

/**
 * DefineConsoleActionsEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2
 */
class DefineConsoleActionsEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var array The additional actions that should be available to console controllers.
     *
     * See [[\craft\console\Controller::defineActions()]] for details on what to set on this.
     */
    public $actions = [];
}
