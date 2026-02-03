<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;

/**
 * @event DefineAdditionalButtons The event that is triggered when defining additional buttons
 * that should be shown at the top of the element's edit page.
 *
 * {@see \CraftCms\Cms\Element\Concerns\HasControlPanelUI::getAdditionalButtons()}
 *
 * @since 6.0.0
 */
final class DefineAdditionalButtons
{
    /**
     * @param  ElementInterface  $element  The element
     * @param  string  $html  The HTML for additional buttons
     */
    public function __construct(
        public ElementInterface $element,
        public string $html = '',
    ) {}
}
