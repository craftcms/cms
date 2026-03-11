<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;
use CraftCms\Cms\Element\Concerns\HasControlPanelUI;

/**
 * @event DefineAltActions The event that is triggered when defining alternative form actions for the element.
 *
 * {@see HasControlPanelUI::getAltActions()}
 */
final class DefineAltActions
{
    public function __construct(
        public ElementInterface $element,
        public array $altActions = [],
    ) {}
}
