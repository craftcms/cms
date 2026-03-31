<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;
use CraftCms\Cms\Element\ElementCaches;

class InvalidateElementCaches
{
    public function __construct(
        /**
         * @var string[] An array of TagDependency tag names that are being invalidated
         */
        public array $tags,

        /**
         * @var ElementInterface|null The element whose caches are being invalidated, if this was triggered from {@see ElementCaches::invalidateForElement()}.
         */
        public ?ElementInterface $element = null,
    ) {}
}
