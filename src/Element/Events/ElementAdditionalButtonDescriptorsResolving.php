<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Concerns\HasControlPanelUI;
use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * @event ElementAdditionalButtonDescriptorsResolving The event that is triggered when defining the buttons shown at the end of an element editor's footer.
 *
 * Items take the same `behavior` descriptors as the action menu (`link`, `download`,
 * `slideout`, `formModal`, …), so they don't submit the editor's form. Items may also
 * set `icon` and `variant`.
 *
 * {@see HasControlPanelUI::additionalButtonDescriptors()}
 *
 * @since 6.0.0
 */
class ElementAdditionalButtonDescriptorsResolving
{
    /** @param list<array<string, mixed>> $items */
    public function __construct(
        public ElementInterface $element,
        public array $items = [],
    ) {}
}
