<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\Data;

use Stringable;

readonly class ResourceEntry implements Stringable
{
    /** @param array<array-key, mixed> $arguments */
    public function __construct(
        private Stringable|string $html,
        public array $arguments,
    ) {}

    public function __toString(): string
    {
        return (string) $this->html;
    }
}
