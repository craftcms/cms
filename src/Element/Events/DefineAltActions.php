<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;

/**
 * @event DefineAltActions The event that is triggered when defining alternative form actions for the element.
 *
 * {@see \CraftCms\Cms\Element\Concerns\HasControlPanelUI::getAltActions()}
 */
final class DefineAltActions
{
    public function __construct(
        public ElementInterface $element,
        public array $altActions = [],
    ) {}
}
