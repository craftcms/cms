<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;
use CraftCms\Cms\Element\Concerns\HasControlPanelUI;
use Stringable;

/**
 * @event DefineAttributeHtml The event that is triggered when defining an attribute's HTML
 * for table and card views.
 *
 * If `html` is set, it will be used instead of the default attribute HTML.
 *
 * {@see HasControlPanelUI::getAttributeHtml()}
 */
final class DefineAttributeHtml
{
    public function __construct(
        public ElementInterface $element,
        public string $attribute,
        public string|Stringable|null $html = null,
    ) {}
}
