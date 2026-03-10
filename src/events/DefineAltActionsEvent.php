<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;

/**
 * DefineAltActionsEvent is used to define form actions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.6.0
 */
class DefineAltActionsEvent extends Event
{
    /**
     * @var array The form actions.
     *
     * See [[\craft\web\CpScreenResponseBehavior::altActions()]] for documentation on supported action properties.
     */
    public array $altActions = [];
}
