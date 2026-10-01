<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * @since 6.0.0
 */
class ElementChipHtmlResolving
{
    public function __construct(
        public ElementInterface $element,
        public string $context,
        public string $html,
    ) {}
}
