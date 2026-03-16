<?php

declare(strict_types=1);

namespace CraftCms\Cms\Condition\Events;

class RegisterConditionRules
{
    public function __construct(
        /**
         * @var string[]|array[] The condition rules types.
         *
         * @phpstan-var string[]|array{class:string}[]
         */
        public array $conditionRules,
    ) {}
}
