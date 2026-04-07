<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;
use CraftCms\Cms\Element\NestedElementManager;

class AfterSaveNestedElements
{
    public function __construct(
        public NestedElementManager $manager,
        /** @param  ElementInterface[]  $elements */
        public array $elements = [],
    ) {}
}
