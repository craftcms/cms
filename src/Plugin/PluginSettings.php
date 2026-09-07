<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin;

use CraftCms\Cms\Component\Component;

abstract class PluginSettings extends Component
{
    public static function create(): static
    {
        /** @phpstan-ignore new.static */
        return new static;
    }

    /** @return array<string, mixed> */
    public function configData(): array
    {
        return $this->validationData();
    }
}
