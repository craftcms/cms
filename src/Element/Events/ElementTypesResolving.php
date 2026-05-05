<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;

class ElementTypesResolving
{
    public function __construct(
        /** @var class-string<ElementInterface>[] */
        public array $types,
    ) {}
}
