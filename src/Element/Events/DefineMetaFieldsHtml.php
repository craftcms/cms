<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Concerns\HasControlPanelUI;
use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * @event DefineMetaFieldsHtml The event that is triggered when defining the HTML for meta fields
 * within the editor sidebar.
 *
 * {@see HasControlPanelUI::metaFieldsHtml()}
 */
class DefineMetaFieldsHtml
{
    /**
     * @param  ElementInterface  $element  The element
     * @param  bool  $static  Whether the fields should be static (non-interactive)
     * @param  string  $html  The HTML for meta fields
     */
    public function __construct(
        public ElementInterface $element,
        public bool $static,
        public string $html = '',
    ) {}
}
