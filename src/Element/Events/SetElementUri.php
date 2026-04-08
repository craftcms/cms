<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;
use CraftCms\Cms\Shared\Concerns\HandleableEvent;

class SetElementUri
{
    use HandleableEvent;

    public function __construct(
        public ElementInterface $element,
    ) {}
}
