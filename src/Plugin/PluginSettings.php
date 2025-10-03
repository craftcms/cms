<?php

namespace CraftCms\Cms\Plugin;

use CraftCms\Cms\Component\Concerns\ValidatableComponent;
use CraftCms\Cms\Component\Contracts\ValidatableComponentInterface;

abstract class PluginSettings implements ValidatableComponentInterface
{
    use ValidatableComponent;
}
