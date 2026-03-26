<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Ruleset
{
    public function __construct(
        /** @var class-string<\CraftCms\Cms\Validation\Ruleset> */
        public string $class,
    ) {}
}
