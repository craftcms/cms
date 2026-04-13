<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Concerns\HasControlPanelUI;
use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * @event DefineMetadata The event that is triggered when defining the element's metadata info.
 *
 * {@see HasControlPanelUI::getMetadata()}
 */
class DefineMetadata
{
    /**
     * @param  ElementInterface  $element  The element
     * @param  array  $metadata  The metadata, with keys representing the labels
     */
    public function __construct(
        public ElementInterface $element,
        public array $metadata = [],
    ) {}
}
