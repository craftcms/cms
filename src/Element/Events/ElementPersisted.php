<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;

/** Dispatched after the element is persisted, before its database transaction commits. */
class ElementPersisted
{
    public function __construct(
        public ElementInterface $element,
        public bool $isNew = false,
    ) {}
}
