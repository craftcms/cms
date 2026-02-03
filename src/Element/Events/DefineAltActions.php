<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;

/**
 * @event DefineAltActions The event that is triggered when defining alternative form actions for the element.
 *
 * {@see \CraftCms\Cms\Element\Concerns\HasControlPanelUI::getAltActions()}
 *
 * @since 6.0.0
 */
final class DefineAltActions
{
    /**
     * @param  ElementInterface  $element  The element
     * @param  array  $altActions  The alternative actions
     */
    public function __construct(
        public ElementInterface $element,
        public array $altActions = [],
    ) {}
}
