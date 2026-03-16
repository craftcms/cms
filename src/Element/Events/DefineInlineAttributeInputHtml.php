<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;
use CraftCms\Cms\Element\Concerns\HasControlPanelUI;
use Stringable;

/**
 * @event DefineInlineAttributeInputHtml The event that is triggered when defining an attribute's
 * inline input HTML.
 *
 * If `html` is set, it will be used instead of the default inline input HTML.
 *
 * {@see HasControlPanelUI::getInlineAttributeInputHtml()}
 */
class DefineInlineAttributeInputHtml
{
    /**
     * @param  ElementInterface  $element  The element
     * @param  string  $attribute  The attribute name
     * @param  string|Stringable|null  $html  The HTML to use (if set, short-circuits default rendering)
     */
    public function __construct(
        public ElementInterface $element,
        public string $attribute,
        public string|Stringable|null $html = null,
    ) {}
}
