<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Concerns\HasControlPanelUI;
use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * @event DefineSidebarHtml The event that is triggered when defining the HTML for the editor sidebar.
 *
 * {@see HasControlPanelUI::getSidebarHtml()}
 */
class DefineSidebarHtml
{
    /**
     * @param  ElementInterface  $element  The element
     * @param  bool  $static  Whether the fields should be static (non-interactive)
     * @param  string  $html  The HTML for the sidebar
     */
    public function __construct(
        public ElementInterface $element,
        public bool $static,
        public string $html = '',
    ) {}
}
