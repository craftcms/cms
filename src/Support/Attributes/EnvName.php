<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Attributes;

use Attribute;

#[Attribute]
final readonly class EnvName
{
    public function __construct(
        public string $name,
    ) {}
}
