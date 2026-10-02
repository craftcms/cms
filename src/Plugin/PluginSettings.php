<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin;

use CraftCms\Cms\Component\Component;

/**
 * @since 6.0.0
 */
abstract class PluginSettings extends Component
{
    /** @return array<string, mixed> */
    public function configData(): array
    {
        return $this->validationData();
    }
}
