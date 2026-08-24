<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Data;

use CraftCms\Cms\Component\Component;

class AssetProcessorIndexData extends Component
{
    public string $uid;

    public string $name;

    public string $handle;

    public string $driver;

    public bool $isDefault;

    public ?string $deleteDisabledReason = null;
}
