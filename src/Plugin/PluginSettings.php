<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin;

use CraftCms\Cms\Component\Concerns\ValidatableComponent;
use CraftCms\Cms\Component\Validation\Contracts\ValidatableComponentInterface;

abstract class PluginSettings implements ValidatableComponentInterface
{
    use ValidatableComponent;
}
