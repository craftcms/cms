<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Concerns\HasControlPanelUI;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Enums\ElementActionContext;

/**
 * @event ElementActionMenuDescriptorsResolving The event that is triggered when defining an element's action menu descriptors.
 *
 * Items name a `behavior` the client dispatches (`link`, `submit`, …) rather than
 * pairing markup with an inline script. Destructive items should set `destructive`,
 * and are only shown outside the element's own edit screen if they set `showInChips`.
 *
 * {@see HasControlPanelUI::actionMenuDescriptors()}
 *
 * @since 6.0.0
 */
class ElementActionMenuDescriptorsResolving
{
    /** @param list<array<string, mixed>> $items */
    public function __construct(
        public ElementInterface $element,
        public ElementActionContext $context,
        public array $items = [],
    ) {}
}
