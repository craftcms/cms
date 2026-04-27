<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Concerns\HasControlPanelUI;
use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * @event RegisterHtmlAttributes The event that is triggered when registering the HTML attributes
 * that should be included in the element's DOM representation in the control panel.
 *
 * {@see HasControlPanelUI::getHtmlAttributes()}
 */
class RegisterHtmlAttributes
{
    /**
     * @param  ElementInterface  $element  The element
     * @param  string  $context  The context that the element is being rendered in
     * @param  array  $htmlAttributes  The HTML attributes
     */
    public function __construct(
        public ElementInterface $element,
        public string $context,
        public array $htmlAttributes = [],
    ) {}
}
