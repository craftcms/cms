<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Events;

class TransformArgumentsPreparing
{
    /** @param array<string, mixed> $arguments */
    public function __construct(
        public array $arguments,
        public bool $handled = false,
    ) {}
}
