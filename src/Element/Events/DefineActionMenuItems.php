<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;

/**
 * @event DefineActionMenuItems The event that is triggered when defining action menu items.
 *
 * {@see \CraftCms\Cms\Element\Concerns\HasControlPanelUI::getActionMenuItems()}
 *
 * @since 6.0.0
 */
final class DefineActionMenuItems
{
    /**
     * @param  ElementInterface  $element  The element
     * @param  array  $items  The action menu items
     */
    public function __construct(
        public ElementInterface $element,
        public array $items = [],
    ) {}
}
