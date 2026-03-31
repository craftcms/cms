<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;
use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

class BeforeUpdateSearchIndex
{
    use ValidatableEvent;

    public function __construct(
        public ElementInterface $element,
    ) {}
}
