<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Data\EagerLoadPlan;

class BeforeEagerLoadElements
{
    public function __construct(
        /**
         * @var class-string<ElementInterface> The source element type
         */
        public string $elementType,

        /**
         * @var ElementInterface[] The source elements
         */
        public array $elements,

        /**
         * @var EagerLoadPlan[] The eager-loading plans
         */
        public array $with
    ) {}
}
