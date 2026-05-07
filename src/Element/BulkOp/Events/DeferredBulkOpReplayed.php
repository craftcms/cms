<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\BulkOp\Events;

class DeferredBulkOpReplayed
{
    public function __construct(
        public string $key,
        public string $event,
        public string $watchKey,
        public mixed $data = null,
    ) {}
}
