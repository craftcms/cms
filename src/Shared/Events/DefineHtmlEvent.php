<?php

declare(strict_types=1);

namespace CraftCms\Cms\Shared\Events;

abstract class DefineHtmlEvent
{
    public function __construct(
        /** @var string The UI component’s HTML */
        public string $html = '',

        /** @var bool Whether the HTML should be static (non-interactive) */
        public bool $static = false,
    ) {}
}
