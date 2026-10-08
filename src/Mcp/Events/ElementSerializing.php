<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * @since 6.0.0
 */
class ElementSerializing
{
    /** @param array<string, mixed> $data */
    public function __construct(
        public ElementInterface $element,
        public array $data,
        public bool $public,
    ) {}
}
