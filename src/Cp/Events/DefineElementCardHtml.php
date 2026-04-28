<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;

class DefineElementCardHtml
{
    public function __construct(
        public ElementInterface $element,
        public string $context,
        public string $html,
    ) {}
}
