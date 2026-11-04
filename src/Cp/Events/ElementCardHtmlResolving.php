<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Events;

use CraftCms\Cms\Cp\Data\ElementCardParts;
use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * Fired for every element card, whether it’s rendered as a single HTML string
 * or by a card component’s slots.
 *
 * Modify `$parts` to change a card everywhere. Setting `$html` replaces the
 * whole card; for slot-rendered cards it’s split back into parts on a
 * best-effort basis.
 *
 * @since 6.0.0
 */
class ElementCardHtmlResolving
{
    public function __construct(
        public ElementInterface $element,
        public string $context,
        public ElementCardParts $parts,
        public ?string $html = null,
    ) {}
}
