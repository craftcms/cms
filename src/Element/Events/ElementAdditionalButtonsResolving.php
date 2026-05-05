<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Concerns\HasControlPanelUI;
use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * @event ElementAdditionalButtonsResolving The event that is triggered when defining additional buttons
 * that should be shown at the top of the element's edit page.
 *
 * {@see HasControlPanelUI::getAdditionalButtons()}
 */
class ElementAdditionalButtonsResolving
{
    public function __construct(
        public ElementInterface $element,
        public string $html = '',
    ) {}
}
