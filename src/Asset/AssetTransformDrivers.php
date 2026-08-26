<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset;

use CraftCms\Cms\Asset\Contracts\AssetTransformDriver;
use CraftCms\Cms\Asset\Data\AssetTransformDriverDefinition;
use CraftCms\Cms\Asset\Exceptions\AssetTransformDriverNotFoundException;
use CraftCms\Cms\Image\CraftAssetTransformDriver;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Manager;
use InvalidArgumentException;
use Override;

#[Singleton]
class AssetTransformDrivers extends Manager
{
    public function getDefaultDriver(): string
    {
        return 'craft';
    }

    /** @return array<string, AssetTransformDriverDefinition> */
    public function definitions(): array
    {
        $definitions = ['craft' => $this->driver('craft')->definition()];

        foreach (array_keys($this->customCreators) as $handle) {
            $definitions[$handle] = $this->driver($handle)->definition();
        }

        return $definitions;
    }

    /**
     * @param  string|null  $driver
     *
     * @throws AssetTransformDriverNotFoundException
     */
    #[Override]
    public function driver($driver = null): AssetTransformDriver
    {
        $handle = $driver ?? $this->getDefaultDriver();

        if (! is_string($handle) || $handle === '') {
            throw new AssetTransformDriverNotFoundException('The selected Asset Transform driver is invalid.');
        }

        try {
            $resolved = parent::driver($handle);
        } catch (InvalidArgumentException $exception) {
            throw new AssetTransformDriverNotFoundException("Asset Transform driver [{$handle}] is not registered.", previous: $exception);
        }

        if (! $resolved instanceof AssetTransformDriver) {
            throw new AssetTransformDriverNotFoundException("Asset Transform driver [{$handle}] is invalid.");
        }

        return $resolved;
    }

    public function has(string $handle): bool
    {
        try {
            $this->driver($handle);

            return true;
        } catch (AssetTransformDriverNotFoundException) {
            return false;
        }
    }

    protected function createCraftDriver(): AssetTransformDriver
    {
        return $this->container->make(CraftAssetTransformDriver::class);
    }
}
