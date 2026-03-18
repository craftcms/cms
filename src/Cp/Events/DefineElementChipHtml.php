<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Events;

use craft\base\ElementInterface;

class DefineElementChipHtml
{
    public function __construct(
        public ElementInterface $element,
        public string $context,
        public string $html,
    ) {}
}
