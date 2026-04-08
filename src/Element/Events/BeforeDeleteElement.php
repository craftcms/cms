<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;

class BeforeDeleteElement
{
    public function __construct(
        public ElementInterface $element,
        public bool $hardDelete = false,
    ) {}
}
