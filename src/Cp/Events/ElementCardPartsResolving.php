<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Events;

use CraftCms\Cms\Cp\Data\ElementCardParts;
use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * Fired for every element card, whether it’s rendered as a single HTML string
 * or by a card component’s slots. Listeners modify `$parts` to change the card
 * wherever it’s shown.
 *
 * @since 6.0.0
 */
class ElementCardPartsResolving
{
    public function __construct(
        public ElementInterface $element,
        public string $context,
        public ElementCardParts $parts,
    ) {}
}
