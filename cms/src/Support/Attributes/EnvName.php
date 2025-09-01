<?php

namespace CraftCms\Cms\Support\Attributes;

use Attribute;

/** @since 6.0.0 */
#[Attribute]
final readonly class EnvName
{
    public function __construct(
        public string $name,
    ) {}
}
