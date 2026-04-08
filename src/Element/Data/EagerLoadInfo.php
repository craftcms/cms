<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Data;

use craft\base\ElementInterface;

class EagerLoadInfo
{
    public function __construct(
        public EagerLoadPlan $plan,

        /**
         * @var ElementInterface[]
         */
        public array $sourceElements,
    ) {}
}
