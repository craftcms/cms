<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;

/**
 * @event DefineSidebarHtml The event that is triggered when defining the HTML for the editor sidebar.
 *
 * {@see \CraftCms\Cms\Element\Concerns\HasControlPanelUI::getSidebarHtml()}
 */
final class DefineSidebarHtml
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
