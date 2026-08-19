<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Data;

use CraftCms\Cms\Asset\Elements\Asset;

readonly class AssetTransformRequest
{
    /**
     * @param  array<string, mixed>  $operations
     * @param  array<string, mixed>  $settings
     */
    public function __construct(
        public Asset $asset,
        public string $driver,
        public array $operations,
        public array $settings,
    ) {}

    public function __debugInfo(): array
    {
        return [
            'asset' => $this->asset,
            'driver' => $this->driver,
            'operations' => $this->operations,
            'settings' => '[redacted]',
        ];
    }
}
