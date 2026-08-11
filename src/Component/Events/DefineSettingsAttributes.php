<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Events;

use CraftCms\Cms\Component\Contracts\ConfigurableComponentInterface;

class DefineSettingsAttributes
{
    /** @param list<string> $attributes */
    public function __construct(
        public ConfigurableComponentInterface $component,
        public array $attributes,
    ) {}
}
