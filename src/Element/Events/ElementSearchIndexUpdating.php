<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

/**
 * @since 6.0.0
 */
class ElementSearchIndexUpdating
{
    use ValidatableEvent;

    public function __construct(
        public ElementInterface $element,
    ) {}
}
