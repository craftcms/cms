<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Data;

/**
 * A transform driver and its inline settings, as configured by a disk’s `asset_transformer` key or passed to
 * `transform()`.
 *
 * @since 6.0.0
 */
readonly class AssetTransformer
{
    /**
     * @param  string  $driver  The registered driver name.
     * @param  array<string, mixed>  $settings  Inline settings, which the driver merges over its own config.
     */
    public function __construct(
        public string $driver,
        public array $settings = [],
    ) {}

    /** @return array{driver: string, settings: string} */
    public function __debugInfo(): array
    {
        return [
            'driver' => $this->driver,
            'settings' => '[redacted]',
        ];
    }
}
