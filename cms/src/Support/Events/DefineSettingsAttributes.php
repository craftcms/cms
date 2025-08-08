<?php

namespace CraftCms\Cms\Support\Events;

use CraftCms\Cms\Support\Contracts\ConfigurableComponentInterface;

final class DefineSettingsAttributes
{
    public function __construct(
        public ConfigurableComponentInterface $component,
        public array $attributes,
    ) {}
}
