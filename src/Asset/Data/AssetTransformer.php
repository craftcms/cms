<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Data;

use CraftCms\Cms\Asset\AssetTransformers;
use CraftCms\Cms\Support\Facades\Deprecator;
use Error;

/**
 * The named transformer a volume uses, from the `assetTransformers` general config setting.
 *
 * @property-read string $driver The transformer’s driver name. Deprecated in 6.0.0; use [[$name]] instead.
 *
 * @since 6.0.0
 */
readonly class AssetTransformer
{
    /**
     * @param  string  $name  The transformer’s name in the `assetTransformers` general config setting.
     */
    public function __construct(
        public string $name,
    ) {}

    public function __get(string $name): mixed
    {
        if ($name !== 'driver') {
            throw new Error(sprintf('Undefined property: %s::$%s', self::class, $name));
        }

        Deprecator::log(self::class.'::$driver', '`AssetTransformer::$driver` is deprecated. Use `$name` instead.');

        $driver = app(AssetTransformers::class)->getInstanceConfig($this->name)['driver'] ?? null;

        return is_string($driver) ? $driver : $this->name;
    }

    public function __isset(string $name): bool
    {
        return $name === 'driver';
    }
}
