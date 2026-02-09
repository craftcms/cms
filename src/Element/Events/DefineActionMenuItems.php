<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;

/**
 * @event DefineActionMenuItems The event that is triggered when defining action menu items.
 *
 * {@see \CraftCms\Cms\Element\Concerns\HasControlPanelUI::getActionMenuItems()}
 */
class DefineActionMenuItems
{
    public function __construct(
        public ElementInterface $element,
        public array $items = [],
    ) {}
}
