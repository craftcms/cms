<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\BulkOp\Events;

class BulkOpStarting
{
    public function __construct(
        public string $key,
    ) {}
}
