<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Validation\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Ruleset
{
    public function __construct(
        /** @var class-string<\CraftCms\Cms\Component\Validation\Ruleset> */
        public string $class,
    ) {}
}
