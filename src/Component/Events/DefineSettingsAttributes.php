<?php

namespace CraftCms\Cms\Component\Events;

use CraftCms\Cms\Component\Contracts\ConfigurableComponentInterface;

/**
 * @since 6.0.0
 */
final class DefineSettingsAttributes
{
    public function __construct(
        public ConfigurableComponentInterface $component,
        public array $attributes,
    ) {}
}
