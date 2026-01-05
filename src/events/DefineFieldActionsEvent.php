<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\ElementInterface;
use craft\base\Event;

/**
 * DefineFieldActionsEvent event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.9.0
 */
class DefineFieldActionsEvent extends DefineMenuItemsEvent
{
    /**
     * @var ElementInterface|null $element The element the form is being rendered for
     */
    public ?ElementInterface $element = null;

    /**
     * @var bool $static Whether the form should be static (non-interactive)
     */
    public bool $static;
}
