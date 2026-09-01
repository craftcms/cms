<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Events;

use CraftCms\Cms\Element\Queries\ElementQuery;

class ElementQueryCacheTagsResolving
{
    public function __construct(
        /** @var ElementQuery<*> */
        public ElementQuery $query,
        /** @var string[] */
        public array $tags,
    ) {}
}
