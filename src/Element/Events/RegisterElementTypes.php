<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;

class RegisterElementTypes
{
    public function __construct(
        /** @var class-string<ElementInterface>[] */
        public array $types,
    ) {}
}
