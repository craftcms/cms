<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset;

use CraftCms\Cms\Asset\Contracts\AssetTransformDriver;
use CraftCms\Cms\Asset\Data\AssetTransformRequest;
use CraftCms\Cms\Asset\Data\AssetTransformResult;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Exceptions\AssetTransformDriverNotFoundException;
use CraftCms\Cms\Asset\Exceptions\InvalidAssetTransformException;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Image\Enums\ImageTransformFormat;
use CraftCms\Cms\Image\Enums\ImageTransformInterlace;
use CraftCms\Cms\Image\Enums\ImageTransformMode;
use CraftCms\Cms\Image\Enums\ImageTransformPosition;
use CraftCms\Cms\Image\ImageTransformer;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Manager;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Override;
use Stringable;

#[Singleton]
class AssetTransforms extends Manager
{
    /** @var array<string, non-empty-list<string|Stringable>> */
    private readonly array $operations;

    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->operations = [
            'fill' => ['string'],
            'format' => ['string', Rule::in(ImageTransformFormat::cases())],
            'height' => ['integer', 'min:1'],
            'interlace' => ['string', Rule::in(ImageTransformInterlace::cases())],
            'mode' => ['string', Rule::in(ImageTransformMode::cases())],
            'position' => ['string', Rule::in(ImageTransformPosition::cases())],
            'quality' => ['integer', 'between:1,100'],
            'upscale' => ['boolean'],
            'width' => ['integer', 'min:1'],
        ];
    }

    public function getDefaultDriver(): string
    {
        return Cms::config()->defaultAssetTransformDriver;
    }

    public function transform(Asset $asset, #[\SensitiveParameter] mixed $definition): AssetTransformResult
    {
        if (! is_array($definition)) {
            throw new InvalidAssetTransformException('An Asset Transform definition must be an array.');
        }

        $driverHandle = array_key_exists('driver', $definition)
            ? Arr::pull($definition, 'driver')
            : $this->getDefaultDriver();

        if (! is_string($driverHandle) || $driverHandle === '') {
            throw new AssetTransformDriverNotFoundException('The selected Asset Transform driver is invalid.');
        }

        $driver = $this->driver($driverHandle);
        $operations = $this->operationsFor($driver);
        $normalized = [];

        foreach ($definition as $handle => $value) {
            if (! is_string($handle) || ! isset($operations[$handle])) {
                throw new InvalidAssetTransformException("Unknown Asset Transform operation [{$handle}].");
            }

            $normalized[$handle] = $this->normalizeOperation($value, $operations[$handle]);
        }

        ksort($normalized);

        return $driver->transform(new AssetTransformRequest($asset, $driverHandle, $normalized, []));
    }

    /** @param string|null $driver */
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

    protected function createCraftDriver(): AssetTransformDriver
    {
        return $this->container->make(ImageTransformer::class);
    }

    /** @return array<string, non-empty-list<string|Stringable>> */
    private function operationsFor(AssetTransformDriver $driver): array
    {
        $operations = $this->operations;

        foreach ($driver->definition()->operations as $handle => $rules) {
            if (! is_string($handle) || $handle === '') {
                throw new InvalidAssetTransformException('Asset Transform operation handles must be non-empty strings.');
            }

            if (isset($operations[$handle])) {
                throw new InvalidAssetTransformException("Asset Transform operation [{$handle}] is reserved by Craft.");
            }

            $operations[$handle] = $rules;
        }

        return $operations;
    }

    /** @param non-empty-list<string|Stringable> $rules */
    private function normalizeOperation(mixed $value, array $rules): bool|float|int|string|null
    {
        if ($value === null) {
            return null;
        }

        $normalized = match ($rules[0]) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE),
            'integer' => filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE),
            'numeric' => filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE),
            'string' => is_scalar($value) ? (string) $value : null,
            default => throw new InvalidAssetTransformException('Invalid Asset Transform operation type.'),
        };

        if ($normalized === null || Validator::make(['value' => $normalized], ['value' => $rules])->fails()) {
            throw new InvalidAssetTransformException('Invalid Asset Transform operation value.');
        }

        return $normalized;
    }
}
