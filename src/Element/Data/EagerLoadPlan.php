<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Data;

use Closure;

class EagerLoadPlan
{
    /** @param array<string, mixed> $criteria */
    public function __construct(
        public ?string $handle = null,
        public ?string $alias = null,
        public array $criteria = [],
        public bool $all = false,
        public bool $count = false,

        /**
         * @var Closure|null A closure whose return value determines whether to apply eager-loaded elements to the given element.
         *
         * The signature of the closure should be `function (\CraftCms\Cms\Element\Contracts\ElementInterface $element): bool`, where `$element` refers to the element
         * the eager-loaded elements are about to be applied to. The closure should return a boolean value.
         */
        public ?Closure $when = null,

        /**
         * @var EagerLoadPlan[] Nested eager-loading plans to apply to the eager-loaded elements.
         */
        public array $nested = [],

        public bool $lazy = false,
    ) {}
}
