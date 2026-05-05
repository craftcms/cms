<?php

declare(strict_types=1);

namespace CraftCms\Cms\Condition\Events;

use CraftCms\Cms\Condition\Contracts\ConditionInterface;

class ConditionRulesResolving
{
    public function __construct(
        public ConditionInterface $condition,

        /**
         * @var string[]|array[] The condition rules types.
         *
         * @phpstan-var string[]|array{class:string}[]
         */
        public array $conditionRules,
    ) {}
}
