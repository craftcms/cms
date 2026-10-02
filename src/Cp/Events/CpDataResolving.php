<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Events;

/**
 * @since 6.0.0
 */
class CpDataResolving
{
    /** @param array<string, mixed> $data */
    public function __construct(
        public array $data = [],
    ) {}
}
