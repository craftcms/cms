<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;

class BeforeMergeCanonicalChanges
{
    public function __construct(
        public ElementInterface $element,
    ) {}
}
