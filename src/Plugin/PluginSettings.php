<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin;

use CraftCms\Cms\Validation\Concerns\ValidatableComponent;
use CraftCms\Cms\Validation\Contracts\Validatable;

abstract class PluginSettings implements Validatable
{
    use ValidatableComponent;
}
