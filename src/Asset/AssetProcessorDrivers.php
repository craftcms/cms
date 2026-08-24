<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset;

use CraftCms\Cms\Asset\Contracts\AssetProcessorDriver;
use CraftCms\Cms\Asset\Data\AssetProcessorDriverDefinition;
use CraftCms\Cms\Asset\Exceptions\AssetProcessorDriverNotFoundException;
use CraftCms\Cms\Image\CraftAssetProcessorDriver;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Manager;
use InvalidArgumentException;
use Override;

#[Singleton]
class AssetProcessorDrivers extends Manager
{
    public function getDefaultDriver(): string
    {
        return 'craft';
    }

    /** @return array<string, AssetProcessorDriverDefinition> */
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
     * @throws AssetProcessorDriverNotFoundException
     */
    #[Override]
    public function driver($driver = null): AssetProcessorDriver
    {
        $handle = $driver ?? $this->getDefaultDriver();

        if (! is_string($handle) || $handle === '') {
            throw new AssetProcessorDriverNotFoundException('The selected Asset Processor driver is invalid.');
        }

        try {
            $resolved = parent::driver($handle);
        } catch (InvalidArgumentException $exception) {
            throw new AssetProcessorDriverNotFoundException("Asset Processor driver [{$handle}] is not registered.", previous: $exception);
        }

        if (! $resolved instanceof AssetProcessorDriver) {
            throw new AssetProcessorDriverNotFoundException("Asset Processor driver [{$handle}] is invalid.");
        }

        return $resolved;
    }

    public function has(string $handle): bool
    {
        try {
            $this->driver($handle);

            return true;
        } catch (AssetProcessorDriverNotFoundException) {
            return false;
        }
    }

    protected function createCraftDriver(): AssetProcessorDriver
    {
        return $this->container->make(CraftAssetProcessorDriver::class);
    }
}
