<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element;

/**
 * @event DefineCacheTags The event that is triggered when defining the cache tags that should be cleared when
 * an element is saved.
 *
 * {@see Element::getCacheTags()}
 */
class DefineCacheTags
{
    public function __construct(
        public ElementInterface $element,
        /** @var string[] */
        public array $tags = [],
    ) {}
}
