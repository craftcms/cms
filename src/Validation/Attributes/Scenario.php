<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
final class Scenario
{
    public function __construct(
        /**
         * The scenario to use for validation.
         */
        public string $scenario,
    ) {}
}
