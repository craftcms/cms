<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries\Events;

use craft\base\ElementInterface;

final class ElementsHydrated
{
    public function __construct(
        /**
         * @var array<ElementInterface> The populated elements
         */
        public array $elements,

        /**
         * @var array[] The element query’s raw result data
         */
        public array $rows,
    ) {}
}
