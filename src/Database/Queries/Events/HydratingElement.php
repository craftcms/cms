<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries\Events;

use craft\base\ElementInterface;

final class HydratingElement
{
    public function __construct(
        public array $row,
        public ?ElementInterface $element = null,
    ) {}
}
