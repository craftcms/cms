<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset;

use CraftCms\Cms\Asset\Contracts\AssetTransformDriver;
use CraftCms\Cms\Asset\Data\AssetTransformDriverDefinition;
use CraftCms\Cms\Asset\Exceptions\AssetTransformDriverNotFoundException;
use CraftCms\Cms\Image\CraftAssetTransformDriver;
use CraftCms\Cms\Support\Arr;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Manager;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Override;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
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

    /**
     * Filters settings down to the ones the driver’s settings fields define.
     *
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function settings(string $driver, array $settings): array
    {
        $handles = array_map(function ($field): string {
            $path = $field->getControl()?->path();
            $path = is_array($path) && count($path) === 1 ? $path[0] : $path;

            if (! is_string($path) || $path === '' || str_contains($path, '.')) {
                throw ValidationException::withMessages([
                    'driver' => t('The selected Asset Transform driver has invalid settings.'),
                ]);
            }

            return $path;
        }, $this->driver($driver)->definition()->settingsFields);

        return Arr::only($settings, $handles);
    }

    protected function createCraftDriver(): AssetTransformDriver
    {
        return $this->container->make(CraftAssetTransformDriver::class);
    }
}
