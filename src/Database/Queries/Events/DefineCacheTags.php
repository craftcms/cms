<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries\Events;

use CraftCms\Cms\Database\Queries\ElementQuery;

final class DefineCacheTags
{
    public function __construct(
        public ElementQuery $query,
        /** @var string[] */
        public array $tags,
    ) {}
}
