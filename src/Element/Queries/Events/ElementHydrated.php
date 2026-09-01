<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;

class ElementHydrated
{
    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $content
     */
    public function __construct(
        public ElementInterface $element,
        public array $row,
        public array $content,
    ) {}
}
