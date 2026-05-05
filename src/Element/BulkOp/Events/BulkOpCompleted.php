<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\BulkOp\Events;

class BulkOpCompleted
{
    public function __construct(
        public string $key,
    ) {}
}
