<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries\Events;

use craft\base\ElementInterface;
use Illuminate\Support\Collection;

final class ElementsHydrated
{
    public function __construct(
        /**
         * @var Collection<ElementInterface> The populated elements
         */
        public Collection $elements,

        /**
         * @var array[] The element query’s raw result data
         */
        public array $rows,
    ) {}
}
