<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset;

use Closure;
use CraftCms\Cms\Asset\Contracts\AssetTransformDriver;
use CraftCms\Cms\Asset\Contracts\PreloadsAssetTransforms;
use CraftCms\Cms\Asset\Data\AssetTransformDriverDefinition;
use CraftCms\Cms\Asset\Data\AssetTransformer;
use CraftCms\Cms\Asset\Data\AssetTransformRequest;
use CraftCms\Cms\Asset\Data\AssetTransformResult;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Exceptions\AssetTransformDriverNotFoundException;
use CraftCms\Cms\Asset\Exceptions\ImageTransformException;
use CraftCms\Cms\Asset\Exceptions\InvalidAssetTransformException;
use CraftCms\Cms\Image\CraftAssetTransformDriver;
use CraftCms\Cms\Image\Enums\ImageTransformFormat;
use CraftCms\Cms\Image\Enums\ImageTransformInterlace;
use CraftCms\Cms\Image\Enums\ImageTransformMode;
use CraftCms\Cms\Image\Enums\ImageTransformPosition;
use CraftCms\Cms\Image\Events\AssetTransformsInvalidating;
use CraftCms\Cms\Image\ImageTransformHelper;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Ui\Nodes\Field;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Manager;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Override;
use Stringable;
use WeakMap;

/**
 * Registers asset transform drivers, resolves which transformer each disk uses, and runs transforms.
 *
 * A disk picks its transformer with an `asset_transformer` key in `config/filesystems.php`: a driver name, or an
 * array with a `driver` key and inline settings. A disk without the key uses the `craft` driver, and a `scoped` disk
 * without the key follows the disk it wraps.
 *
 * @since 6.0.0
 */
#[Singleton]
class AssetTransformers extends Manager
{
    private const int MAX_SCOPED_DEPTH = 8;

    /** @var array<string, non-empty-list<string|Stringable>> */
    private readonly array $parameterRules;

    /** @var WeakMap<AssetTransformDriver, array<string, array<string, mixed>>> */
    private WeakMap $validatedParameters;

    /** @var array<string, true> Invalid transformer config warnings already logged by this process. */
    private array $reportedInvalidConfig = [];

    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->validatedParameters = new WeakMap;

        $this->parameterRules = [
            'fill' => ['string'],
            'format' => ['string', Rule::enum(ImageTransformFormat::class)],
            'height' => ['integer', 'min:1'],
            'interlace' => ['string', Rule::enum(ImageTransformInterlace::class)],
            'mode' => ['string', Rule::enum(ImageTransformMode::class)],
            'position' => ['string', Rule::enum(ImageTransformPosition::class)],
            'quality' => ['integer', 'between:1,100'],
            'upscale' => ['boolean'],
            'width' => ['integer', 'min:1'],
        ];
    }

    public function getDefaultDriver(): string
    {
        return 'craft';
    }

    /**
     * @param  string|null  $driver
     *
     * @throws AssetTransformDriverNotFoundException
     */
    #[Override]
    public function driver($driver = null): AssetTransformDriver
    {
        $name = $driver ?? $this->getDefaultDriver();

        if (! is_string($name) || $name === '') {
            throw new AssetTransformDriverNotFoundException('The selected asset transform driver is invalid.');
        }

        // Only exact registered names. Laravel's Manager would otherwise map names like `CRAFT` or `-` onto its
        // own `create…Driver()` methods.
        if ($name !== 'craft' && ! isset($this->customCreators[$name])) {
            throw new AssetTransformDriverNotFoundException("Asset transform driver [{$name}] is not registered.");
        }

        try {
            $resolved = parent::driver($name);
        } catch (InvalidArgumentException $exception) {
            throw new AssetTransformDriverNotFoundException("Asset transform driver [{$name}] is not registered.", previous: $exception);
        }

        if (! $resolved instanceof AssetTransformDriver) {
            throw new AssetTransformDriverNotFoundException("Asset transform driver [{$name}] is invalid.");
        }

        return $resolved;
    }

    public function has(string $driver): bool
    {
        try {
            $this->driver($driver);

            return true;
        } catch (AssetTransformDriverNotFoundException) {
            return false;
        }
    }

    /** @return array<string, AssetTransformDriverDefinition> */
    public function definitions(): array
    {
        $definitions = ['craft' => $this->driver('craft')->definition()];

        foreach (array_keys($this->customCreators) as $name) {
            $definitions[$name] = $this->driver($name)->definition();
        }

        return $definitions;
    }

    /**
     * Returns the transformer for files on a disk.
     *
     * Invalid config (a malformed value, or an unregistered driver) throws when debug mode is enabled. Otherwise it’s
     * logged and the disk uses the `craft` driver.
     *
     * @param  string|null  $disk  The disk name, or `null` for files that aren’t on a known disk.
     *
     * @throws InvalidAssetTransformException|AssetTransformDriverNotFoundException
     */
    public function forDisk(?string $disk): AssetTransformer
    {
        $config = $disk !== null ? $this->diskTransformerConfig($disk) : null;

        if ($config === null) {
            return new AssetTransformer('craft');
        }

        return $this->validTransformer($config, "the [{$disk}] disk’s asset_transformer", new AssetTransformer('craft'));
    }

    /**
     * Returns a disk’s raw `asset_transformer` value, following scoped disks, or `null` if none is set. Unlike
     * `forDisk()`, this doesn’t validate the value.
     */
    public function configForDisk(string $disk): mixed
    {
        return $this->diskTransformerConfig($disk);
    }

    /**
     * @param  string|null  $transformer  A driver name that overrides the disk’s transformer.
     */
    public function transform(
        Asset $asset,
        #[\SensitiveParameter] mixed $definition,
        ?string $transformer = null,
    ): AssetTransformResult {
        $request = $this->request($asset, $definition, $this->transformerFor($asset, $transformer));

        return $this->driver($request->transformer->driver)->transform($request);
    }

    public function invalidate(Asset $asset): void
    {
        event(new AssetTransformsInvalidating($asset));
    }

    /**
     * @param  list<Asset>  $assets
     * @param  list<mixed>|(Closure(Asset): list<mixed>)  $definitions
     */
    public function preload(array $assets, #[\SensitiveParameter] array|Closure $definitions): void
    {
        $requestsByDriver = [];

        foreach ($assets as $asset) {
            $assetDefinitions = $definitions instanceof Closure ? $definitions($asset) : $definitions;

            foreach ($this->preloadRequests($asset, $assetDefinitions) as $request) {
                $requestsByDriver[$request->transformer->driver][] = $request;
            }
        }

        foreach ($requestsByDriver as $driverName => $requests) {
            $driver = $this->driver($driverName);

            if ($driver instanceof PreloadsAssetTransforms) {
                $driver->preloadAssetTransforms($requests);
            }
        }
    }

    /** @return array<string, non-empty-list<string|Stringable>> */
    public function parameterRules(string $driver): array
    {
        $parameterRules = $this->parameterRules;
        $driverRules = $this->driver($driver)->definition()->parameterRules;

        foreach ($driverRules as $name => $rules) {
            if (! is_string($name) || $name === '') {
                throw new InvalidAssetTransformException('Asset transform parameter names must be non-empty strings.');
            }

            $parameterRules[$name] = $rules;
        }

        return $parameterRules;
    }

    /** @return array<string, non-empty-list<string|Stringable>> */
    public function coreParameterRules(): array
    {
        return $this->parameterRules;
    }

    /** @return array<string, Field> */
    public function parameterFields(string $driver): array
    {
        $definition = $this->driver($driver)->definition();
        $rules = $this->parameterRules($driver);

        foreach ($definition->parameterFields as $name => $field) {
            $path = $field->getControl()?->path();
            $path = is_array($path) && count($path) === 1 ? $path[0] : $path;

            if (! is_string($name) || ! isset($definition->parameterRules[$name], $rules[$name]) || $path !== $name) {
                throw new InvalidAssetTransformException('Asset transform parameter fields must match a declared parameter name.');
            }
        }

        return $definition->parameterFields;
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function validateParameters(string $driver, array $parameters): array
    {
        $rules = $this->parameterRules($driver);
        $rules = Arr::only($rules, array_keys($parameters));
        $rules = array_map(fn (array $rules): array => ['nullable', ...$rules], $rules);

        if (Validator::make($parameters, $rules)->fails()) {
            throw new InvalidAssetTransformException('Invalid asset transform parameter value.');
        }

        ksort($parameters);

        return $parameters;
    }

    protected function createCraftDriver(): AssetTransformDriver
    {
        return $this->container->make(CraftAssetTransformDriver::class);
    }

    /**
     * Returns a disk’s `asset_transformer` value. A scoped disk without one follows the disk it wraps.
     *
     * Disks are read from the `disks` array rather than with dot notation, so disk names can contain dots.
     *
     * @param  string|array<string, mixed>  $disk  A disk name, or a scoped disk’s inline `disk` config.
     */
    private function diskTransformerConfig(string|array $disk, int $depth = 0): mixed
    {
        $config = is_string($disk) ? ($this->config->get('filesystems.disks')[$disk] ?? null) : $disk;

        if (! is_array($config) || $depth > self::MAX_SCOPED_DEPTH) {
            return null;
        }

        if (array_key_exists('asset_transformer', $config)) {
            return $config['asset_transformer'];
        }

        $wrapped = $config['disk'] ?? null;

        return ($config['driver'] ?? null) === 'scoped' && (is_string($wrapped) || is_array($wrapped))
            ? $this->diskTransformerConfig($wrapped, $depth + 1)
            : null;
    }

    /**
     * Returns the transformer for one request: the `transformer` argument if given, otherwise the asset’s disk’s.
     *
     * An argument naming the disk’s own driver keeps the disk’s settings. Any other driver name gets no inline
     * settings, so a driver can hand a file to another one, such as `craft`.
     */
    private function transformerFor(Asset $asset, ?string $override): AssetTransformer
    {
        $diskTransformer = $this->forDisk($asset->getVolume()->getResolvedFsTarget());

        if ($override === null || $override === $diskTransformer->driver) {
            return $diskTransformer;
        }

        return $this->validTransformer($override, 'the transformer argument', $diskTransformer);
    }

    /**
     * @param  string  $source  Where the config came from, for error messages.
     * @param  AssetTransformer  $fallback  Used instead when debug mode is off.
     *
     * @throws InvalidAssetTransformException|AssetTransformDriverNotFoundException
     */
    private function validTransformer(mixed $config, string $source, AssetTransformer $fallback): AssetTransformer
    {
        try {
            $transformer = $this->transformerFromConfig($config, $source);

            if (! $this->has($transformer->driver)) {
                throw new AssetTransformDriverNotFoundException("Asset transform driver [{$transformer->driver}], set by {$source}, is not registered.");
            }

            return $transformer;
        } catch (InvalidAssetTransformException|AssetTransformDriverNotFoundException $exception) {
            if (app()->hasDebugModeEnabled()) {
                throw $exception;
            }

            $message = "{$exception->getMessage()} Using the [{$fallback->driver}] driver instead.";

            if (! isset($this->reportedInvalidConfig[$message])) {
                $this->reportedInvalidConfig[$message] = true;
                Log::warning($message);
            }

            return $fallback;
        }
    }

    /** @throws InvalidAssetTransformException */
    private function transformerFromConfig(mixed $config, string $source): AssetTransformer
    {
        if (is_string($config) && $config !== '') {
            return new AssetTransformer($config);
        }

        $driver = is_array($config) ? ($config['driver'] ?? null) : null;

        if (! is_string($driver) || $driver === '') {
            throw new InvalidAssetTransformException("The asset transformer set by {$source} must be a driver name or an array with a [driver] key.");
        }

        /** @var array<string, mixed> $settings */
        $settings = Arr::except($config, 'driver');

        return new AssetTransformer($driver, $settings);
    }

    /**
     * @param  list<mixed>  $definitions
     * @return list<AssetTransformRequest>
     */
    private function preloadRequests(Asset $asset, array $definitions): array
    {
        $requests = [];
        $referenceRequest = null;
        $transformer = null;

        foreach ($definitions as $definition) {
            try {
                [$size, $unit] = AssetsHelper::parseSrcsetSize($definition);
            } catch (InvalidArgumentException) {
                $transformer ??= $this->transformerFor($asset, null);
                $requests[] = $referenceRequest = $this->request($asset, $definition, $transformer);

                continue;
            }

            $referenceWidth = $referenceRequest?->parameters['width'] ?? null;

            if ($referenceWidth === null) {
                throw new InvalidArgumentException("Can’t preload transform “{$definition}” without a prior transform that specifies the base width");
            }

            $referenceWidth = (int) $referenceWidth;
            $parameters = $referenceRequest->parameters;
            $parameters['width'] = $unit === 'w'
                ? (int) $size
                : (int) ceil($referenceWidth * $size);

            if (isset($referenceRequest->parameters['height'])) {
                $parameters['height'] = $unit === 'w'
                    ? (int) ceil($referenceRequest->parameters['height'] * $parameters['width'] / $referenceWidth)
                    : (int) ceil($referenceRequest->parameters['height'] * $size);
            }

            $requests[] = $this->request($asset, $parameters, $referenceRequest->transformer);
        }

        return $requests;
    }

    private function request(
        Asset $asset,
        #[\SensitiveParameter] mixed $definition,
        AssetTransformer $transformer,
    ): AssetTransformRequest {
        try {
            $parameters = $this->normalizeDefinition($definition, $transformer);
        } catch (ImageTransformException|InvalidArgumentException $exception) {
            throw new InvalidAssetTransformException($exception->getMessage(), previous: $exception);
        }

        ksort($parameters);

        if (array_all($parameters, fn (mixed $value): bool => is_scalar($value) || $value === null)) {
            $driver = $this->driver($transformer->driver);
            $key = serialize($parameters);
            $this->validatedParameters[$driver] ??= [];
            $parameters = $this->validatedParameters[$driver][$key]
                ??= $this->validateParameters($transformer->driver, $parameters);
        } else {
            $parameters = $this->validateParameters($transformer->driver, $parameters);
        }

        return new AssetTransformRequest(
            asset: $asset,
            transformer: $transformer,
            parameters: $parameters,
        );
    }

    /** @return array<string, mixed> */
    private function normalizeDefinition(mixed $definition, AssetTransformer $transformer): array
    {
        if (is_array($definition)) {
            if (! array_key_exists('transform', $definition)) {
                return $definition;
            }

            return [
                ...$this->normalizeDefinition(Arr::pull($definition, 'transform'), $transformer),
                ...$definition,
            ];
        }

        $transform = ImageTransformHelper::normalizeTransform($definition);

        if ($transform === null) {
            throw new InvalidAssetTransformException('A transform definition must be an array, object, or named transform handle.');
        }

        return $transform->getParameters($transformer->driver);
    }
}
