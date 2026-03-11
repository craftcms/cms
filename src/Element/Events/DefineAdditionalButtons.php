<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;
use CraftCms\Cms\Element\Concerns\HasControlPanelUI;

/**
 * @event DefineAdditionalButtons The event that is triggered when defining additional buttons
 * that should be shown at the top of the element's edit page.
 *
 * {@see HasControlPanelUI::getAdditionalButtons()}
 */
final class DefineAdditionalButtons
{
    public function __construct(
        public ElementInterface $element,
        public string $html = '',
    ) {}
}
