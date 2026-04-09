<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Attributes;

use Attribute;

#[Attribute]
final readonly class Importable
{
    public function __construct(
        public string $name,
    ) {}
}
