<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;

class ElementHydrating
{
    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $content
     */
    public function __construct(
        public array $row,
        public array $content,
        public ?ElementInterface $element = null,
    ) {}
}
