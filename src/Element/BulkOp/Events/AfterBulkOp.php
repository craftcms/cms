<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\BulkOp\Events;

class AfterBulkOp
{
    public function __construct(
        public string $key,
    ) {}
}
