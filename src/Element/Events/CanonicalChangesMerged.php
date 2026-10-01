<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * @since 6.0.0
 */
class CanonicalChangesMerged
{
    public function __construct(
        public ElementInterface $element,
    ) {}
}
