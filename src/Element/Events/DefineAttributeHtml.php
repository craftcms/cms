<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;
use Stringable;

/**
 * @event DefineAttributeHtml The event that is triggered when defining an attribute's HTML
 * for table and card views.
 *
 * If `html` is set, it will be used instead of the default attribute HTML.
 *
 * {@see \CraftCms\Cms\Element\Concerns\HasControlPanelUI::getAttributeHtml()}
 *
 * @since 6.0.0
 */
final class DefineAttributeHtml
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
