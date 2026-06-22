<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Events;

use CraftCms\Cms\Field\Link;

/**
 * @see Link::types()
 */
class LinkTypesResolving
{
    public function __construct(
        /** @var string[] List of link types. */
        public array $types = [],
    ) {}
}
