<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Events;

use CraftCms\Cms\Element\Queries\ElementQuery;

class DefineCacheTags
{
    public function __construct(
        public ElementQuery $query,
        /** @var string[] */
        public array $tags,
    ) {}
}
