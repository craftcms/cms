<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset;

use CraftCms\Cms\Asset\Contracts\AssetTransformDriver;
use CraftCms\Cms\Asset\Contracts\PreloadsAssetTransforms;
use CraftCms\Cms\Asset\Data\AssetTransformDriverDefinition;
use CraftCms\Cms\Asset\Data\AssetTransformRequest;
use CraftCms\Cms\Asset\Data\AssetTransformResult;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Exceptions\AssetTransformDriverNotFoundException;
use CraftCms\Cms\Asset\Exceptions\ImageTransformException;
use CraftCms\Cms\Asset\Exceptions\InvalidAssetTransformException;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Image\Enums\ImageTransformFormat;
use CraftCms\Cms\Image\Enums\ImageTransformInterlace;
use CraftCms\Cms\Image\Enums\ImageTransformMode;
use CraftCms\Cms\Image\Enums\ImageTransformPosition;
use CraftCms\Cms\Image\Events\AssetTransformsInvalidating;
use CraftCms\Cms\Image\ImageTransformer;
use CraftCms\Cms\Image\ImageTransformHelper;
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

    /** @return array<string, AssetTransformDriverDefinition> */
    public function getDriverDefinitions(): array
    {
        $definitions = ['craft' => $this->driver('craft')->definition()];

        foreach (array_keys($this->customCreators) as $handle) {
            $definitions[$handle] = $this->driver($handle)->definition();
        }

        return $definitions;
    }

    /** @return array<string, non-empty-list<string|Stringable>> */
    public function getOperationRules(): array
    {
        $operations = $this->operations;

        foreach (array_keys($this->customCreators) as $driverHandle) {
            foreach ($this->driver($driverHandle)->definition()->operations as $handle => $rules) {
                if (! is_string($handle) || $handle === '') {
                    throw new InvalidAssetTransformException('Asset Transform operation handles must be non-empty strings.');
                }

                if (isset($operations[$handle]) && $operations[$handle] != $rules) {
                    throw new InvalidAssetTransformException("Asset Transform operation [{$handle}] has incompatible declarations.");
                }

                $operations[$handle] = $rules;
            }
        }

        return $operations;
    }

    /**
     * @param  array<string, mixed>  $operations
     * @return array<string, mixed>
     */
    public function validateOperations(array $operations): array
    {
        $rules = Arr::only($this->getOperationRules(), array_keys($operations));
        $rules = array_map(fn (array $rules): array => ['nullable', ...$rules], $rules);

        if (Validator::make($operations, $rules)->fails()) {
            throw new InvalidAssetTransformException('Invalid Asset Transform operation value.');
        }

        ksort($operations);

        return $operations;
    }

    /** @return array<string, Field> */
    public function getOperationFields(): array
    {
        $rules = $this->getOperationRules();
        $definitions = $this->getDriverDefinitions();

        $fields = [];

        foreach ($definitions as $definition) {
            foreach ($definition->operationFields as $handle => $field) {
                $path = $field->getControl()?->path();
                $path = is_array($path) && count($path) === 1 ? $path[0] : $path;

                if (! is_string($handle) || ! isset($rules[$handle]) || $path !== $handle) {
                    throw new InvalidAssetTransformException('Asset Transform operation fields must match a declared operation handle.');
                }

                $fields[$handle] ??= $field;
            }
        }

        return $fields;
    }

    /** @param array<string, mixed> $settings */
    public function transform(Asset $asset, #[\SensitiveParameter] mixed $definition, array $settings = [], ?string $candidateDriver = null): AssetTransformResult
    {
        $request = $this->request($asset, $definition, $settings, $candidateDriver);

        return $this->driver($request->driver)->transform($request);
    }

    public function invalidate(Asset $asset): void
    {
        event(new AssetTransformsInvalidating($asset));
    }

    /**
     * @param  list<Asset>  $assets
     * @param  list<mixed>  $definitions
     */
    public function preload(array $assets, #[\SensitiveParameter] array $definitions): void
    {
        $requestsByDriver = [];

        foreach ($assets as $asset) {
            foreach ($this->preloadRequests($asset, $definitions) as $request) {
                $requestsByDriver[$request->driver][] = $request;
            }
        }

        foreach ($requestsByDriver as $driverHandle => $requests) {
            $driver = $this->driver($driverHandle);

            if ($driver instanceof PreloadsAssetTransforms) {
                $driver->preloadAssetTransforms($requests);
            }
        }
    }

    /**
     * @param  list<mixed>  $definitions
     * @return list<AssetTransformRequest>
     */
    private function preloadRequests(Asset $asset, array $definitions): array
    {
        $requests = [];
        $referenceRequest = null;

        foreach ($definitions as $definition) {
            try {
                [$size, $unit] = AssetsHelper::parseSrcsetSize($definition);
            } catch (InvalidArgumentException) {
                $requests[] = $referenceRequest = $this->request($asset, $definition);

                continue;
            }

            $referenceWidth = $referenceRequest?->operations['width'] ?? null;

            if ($referenceWidth === null) {
                throw new InvalidArgumentException("Can’t preload transform “{$definition}” without a prior transform that specifies the base width");
            }

            $referenceWidth = (int) $referenceWidth;
            $operations = $referenceRequest->operations;
            $operations['width'] = $unit === 'w'
                ? (int) $size
                : (int) ceil($referenceWidth * $size);

            if (isset($referenceRequest->operations['height'])) {
                $operations['height'] = $unit === 'w'
                    ? (int) ceil($referenceRequest->operations['height'] * $operations['width'] / $referenceWidth)
                    : (int) ceil($referenceRequest->operations['height'] * $size);
            }

            $requests[] = $this->request($asset, [
                'driver' => $referenceRequest->driver,
                ...$operations,
            ]);
        }

        return $requests;
    }

    /** @param array<string, mixed> $settings */
    private function request(Asset $asset, #[\SensitiveParameter] mixed $definition, array $settings = [], ?string $candidateDriver = null): AssetTransformRequest
    {
        try {
            $definition = $this->normalizeDefinition($definition);
        } catch (ImageTransformException|InvalidArgumentException $exception) {
            throw new InvalidAssetTransformException($exception->getMessage(), previous: $exception);
        }

        $filesystemTransform = $asset->getVolume()->getFs()->getAssetTransform();
        $driverHandle = match (true) {
            array_key_exists('driver', $definition) => Arr::pull($definition, 'driver'),
            is_array($filesystemTransform) && array_key_exists('driver', $filesystemTransform) => $filesystemTransform['driver'],
            $candidateDriver !== null => $candidateDriver,
            default => $this->getDefaultDriver(),
        };

        if (! is_string($driverHandle) || $driverHandle === '') {
            throw new AssetTransformDriverNotFoundException('The selected Asset Transform driver is invalid.');
        }

        $this->driver($driverHandle);
        $operations = $this->validateOperations($definition);

        if (
            is_array($filesystemTransform)
            && ($filesystemTransform['driver'] ?? null) === $driverHandle
            && ! is_array($filesystemTransform['settings'] ?? null)
        ) {
            throw new InvalidAssetTransformException('The selected Asset Transform filesystem settings are invalid.');
        }

        $filesystemSettings = match (true) {
            ! is_array($filesystemTransform) => [],
            ($filesystemTransform['driver'] ?? null) === $driverHandle
                && is_array($filesystemTransform['settings'] ?? null) => $filesystemTransform['settings'],
            default => [],
        };

        return new AssetTransformRequest($asset, $driverHandle, $operations, [
            ...$filesystemSettings,
            ...$settings,
        ]);
    }

    /** @return array<string, mixed> */
    private function normalizeDefinition(mixed $definition): array
    {
        if (is_array($definition)) {
            if (! array_key_exists('transform', $definition)) {
                return $definition;
            }

            return [
                ...$this->normalizeDefinition(Arr::pull($definition, 'transform')),
                ...$definition,
            ];
        }

        $transform = ImageTransformHelper::normalizeTransform($definition);

        if ($transform === null) {
            throw new InvalidAssetTransformException('An Asset Transform definition must be an array, object, or named transform handle.');
        }

        return [
            ...($transform->driver !== null ? ['driver' => $transform->driver] : []),
            ...$transform->getOperations(),
        ];
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
}
