<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\BulkOp\Events;

/**
 * @since 6.0.0
 */
class BulkOpCompleted
{
    public function __construct(
        public string $key,
    ) {}
}
