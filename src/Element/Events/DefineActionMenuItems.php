<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;
use CraftCms\Cms\Element\Concerns\HasControlPanelUI;

/**
 * @event DefineActionMenuItems The event that is triggered when defining action menu items.
 *
 * {@see HasControlPanelUI::getActionMenuItems()}
 */
class DefineActionMenuItems
{
    public function __construct(
        public ElementInterface $element,
        public array $items = [],
    ) {}
}
