<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Attributes;

use Attribute;

/**
 * @since 6.0.0
 */
#[Attribute]
readonly class EnvName
{
    public function __construct(
        public string $name,
    ) {}
}
