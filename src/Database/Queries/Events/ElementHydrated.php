<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries\Events;

use craft\base\ElementInterface;

final class ElementHydrated
{
    public function __construct(
        public ElementInterface $element,
        public array $row,
    ) {}
}
