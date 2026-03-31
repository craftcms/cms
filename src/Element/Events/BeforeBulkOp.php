<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

class BeforeBulkOp
{
    public function __construct(
        public string $key,
    ) {}
}
