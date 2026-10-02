<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Events;

use CraftCms\Cms\Component\Contracts\ConfigurableComponentInterface;

/**
 * @since 6.0.0
 */
class DefineSettingsAttributes
{
    /** @param list<string> $attributes */
    public function __construct(
        public ConfigurableComponentInterface $component,
        public array $attributes,
    ) {}
}
