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
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Exceptions\AssetTransformDriverNotFoundException;
use CraftCms\Cms\Asset\Exceptions\AssetTransformException;
use CraftCms\Cms\Asset\Exceptions\ImageTransformException;
use CraftCms\Cms\Asset\Exceptions\InvalidAssetTransformException;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Image\CraftAssetTransformDriver;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\Enums\ImageTransformFormat;
use CraftCms\Cms\Image\Enums\ImageTransformInterlace;
use CraftCms\Cms\Image\Enums\ImageTransformMode;
use CraftCms\Cms\Image\Enums\ImageTransformPosition;
use CraftCms\Cms\Image\Events\AssetTransformsInvalidating;
use CraftCms\Cms\Image\ImageTransformHelper;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Ui\Nodes\Field;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MultipleInstanceManager;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Override;
use RuntimeException;
use Stringable;
use WeakMap;

/**
 * Registers asset transform drivers, resolves which transformer each volume uses, and runs transforms.
 *
 * Transformers are named in the `assetTransformers` general config setting, each with a `driver` and its settings.
 * Craft defines the `craft` transformer. A volume picks a transformer with its `assetTransformer` setting,
 * a transformer name or an environment variable that names one. A volume without one uses the configured `default`.
 * Drivers other than `craft` are registered with `extend()`.
 *
 * @since 6.0.0
 */
#[Singleton]
class AssetTransformers extends MultipleInstanceManager
{
    /** @internal Hidden context key holding the caller’s immediate generation preference. */
    public const string IMMEDIATE_TRANSFORMS_CONTEXT = self::class.'.immediateTransforms';

    private const int MAX_SCOPED_DEPTH = 8;

    /** Prefix of the names given to transformers set by a disk’s deprecated `asset_transformer` key. */
    private const string LEGACY_DISK_PREFIX = 'disk:';

    /** @var array<string, non-empty-list<string|Stringable>> */
    private readonly array $parameterRules;

    /** @var WeakMap<AssetTransformDriver, array<string, array<string, mixed>>> */
    private WeakMap $validatedParameters;

    /** @var array<string, true> Invalid transformer config warnings already logged by this process. */
    private array $reportedInvalidConfig = [];

    public function __construct(Application $app)
    {
        parent::__construct($app);

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

    #[Override]
    public function getDefaultInstance(): string
    {
        return Cms::config()->assetTransformers->default ?? 'craft';
    }

    /**
     * @param  string  $name
     */
    #[Override]
    public function setDefaultInstance($name): void
    {
        Cms::config()->assetTransformers->default($name);
    }

    /**
     * Returns a transformer’s config from the `assetTransformers` general config setting, or `null` if it isn’t defined.
     *
     * @param  string  $name
     * @return array<string, mixed>|null
     */
    #[Override]
    public function getInstanceConfig($name): ?array
    {
        if (str_starts_with($name, self::LEGACY_DISK_PREFIX)) {
            $config = $this->legacyDiskConfig(substr($name, strlen(self::LEGACY_DISK_PREFIX)));

            return is_array($config) ? $config : null;
        }

        return Cms::config()->assetTransformers->transformerConfig($name);
    }

    /**
     * Returns the driver instance for a named transformer.
     *
     * @throws AssetTransformDriverNotFoundException
     */
    public function transformer(?string $name = null): AssetTransformDriver
    {
        $name ??= $this->getDefaultInstance();
        $config = $name !== '' ? $this->getInstanceConfig($name) : null;

        if ($config === null) {
            throw new AssetTransformDriverNotFoundException("Asset transformer [{$name}] is not defined.");
        }

        $driver = $config['driver'] ?? null;

        // Only exact registered names. Laravel would otherwise map names like `CRAFT` onto `createCraftDriver()`.
        if (! is_string($driver) || ($driver !== 'craft' && ! isset($this->customCreators[$driver]))) {
            $driverName = is_string($driver) ? $driver : get_debug_type($driver);

            throw new AssetTransformDriverNotFoundException("Asset transformer [{$name}] uses the [{$driverName}] driver, which is not registered.");
        }

        try {
            $resolved = $this->instance($name);
        } catch (AssetTransformException $exception) {
            throw $exception;
        } catch (InvalidArgumentException|RuntimeException $exception) {
            throw new AssetTransformDriverNotFoundException("Asset transformer [{$name}] could not be created.", previous: $exception);
        }

        if (! $resolved instanceof AssetTransformDriver) {
            throw new AssetTransformDriverNotFoundException("Asset transformer [{$name}] is invalid.");
        }

        return $resolved;
    }

    /**
     * Returns whether a transformer is defined and its driver is registered, without building it.
     */
    public function has(string $name): bool
    {
        $driver = $name !== '' ? ($this->getInstanceConfig($name)['driver'] ?? null) : null;

        return is_string($driver) && ($driver === 'craft' || isset($this->customCreators[$driver]));
    }

    /**
     * Returns the names of the defined transformers whose drivers are registered, without building them.
     *
     * @return list<string>
     */
    public function names(): array
    {
        return array_values(array_filter(Cms::config()->assetTransformers->names(), $this->has(...)));
    }

    /** @return array<string, AssetTransformDriverDefinition> */
    public function definitions(): array
    {
        $definitions = [];

        foreach ($this->names() as $name) {
            // One transformer whose settings aren’t valid in this environment shouldn’t break the others
            try {
                $definitions[$name] = $this->transformer($name)->definition();
            } catch (AssetTransformException $exception) {
                report($exception);
            }
        }

        return $definitions;
    }

    /**
     * Registers a driver. Its callback receives the container and the config of the transformer being built, the way
     * `Storage::extend()` callbacks receive a disk’s config. Unlike Laravel’s `MultipleInstanceManager`, the callback
     * isn’t rebound to this manager.
     *
     * @param  string  $driver
     *
     * @throws InvalidArgumentException if the name is `craft`.
     */
    #[Override]
    public function extend($driver, Closure $callback): static
    {
        if ($driver === 'craft') {
            throw new InvalidArgumentException('The [craft] asset transform driver name is reserved.');
        }

        $this->customCreators[$driver] = $callback;

        return $this;
    }

    /**
     * Forgets the built transformers, so they’re built again from the current config.
     */
    public function forgetTransformers(): static
    {
        $this->instances = [];

        return $this;
    }

    /**
     * Returns the transformer for a volume’s assets: the volume’s `assetTransformer`, then its disk’s deprecated
     * `asset_transformer` key, then the configured default.
     *
     * Invalid config (an undefined transformer or an unregistered driver) throws when debug mode is enabled.
     * Otherwise it’s logged and the volume uses the `craft` transformer.
     *
     * @throws AssetTransformDriverNotFoundException
     */
    public function forVolume(Volume $volume): AssetTransformer
    {
        if (($name = $volume->getAssetTransformer()) !== null) {
            return $this->validTransformer($name, "the [{$volume->handle}] volume’s assetTransformer", new AssetTransformer('craft'));
        }

        $disk = $volume->getResolvedDisk();
        $legacyConfig = $disk !== null ? $this->legacyDiskConfig($disk) : null;

        if ($legacyConfig !== null) {
            Deprecator::log(
                "filesystems.disks.$disk.asset_transformer",
                "The `asset_transformer` key on the `$disk` disk is deprecated. Set the volume’s Asset Transformer setting or the `assetTransformers` config setting instead. Its `has_urls` setting is ignored.",
            );

            $name = is_string($legacyConfig) ? $legacyConfig : self::LEGACY_DISK_PREFIX.$disk;

            return $this->validTransformer($name, "the [{$disk}] disk’s asset_transformer", new AssetTransformer('craft'));
        }

        $default = Cms::config()->assetTransformers->default;

        if ($default === null) {
            return new AssetTransformer('craft');
        }

        return $this->validTransformer(
            $default,
            'the assetTransformers default',
            new AssetTransformer('craft'),
        );
    }

    /**
     * Returns whether a disk, or a disk it’s scoped to, has a deprecated `asset_transformer` key.
     *
     * @internal
     */
    public function diskChoosesTransformer(string $disk): bool
    {
        return $this->legacyDiskConfig($disk) !== null;
    }

    public function transform(
        Asset $asset,
        #[\SensitiveParameter] mixed $definition,
    ): AssetTransformResult {
        $request = $this->request($asset, $definition, $this->forAsset($asset));

        return $this->transformer($request->transformer->name)->transform($request);
    }

    /**
     * Runs a callback in which transforms are requested immediately, so drivers get `true` in
     * `AssetTransformRequest::$immediately`.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function withImmediateTransforms(callable $callback): mixed
    {
        return Context::scope($callback, hidden: [self::IMMEDIATE_TRANSFORMS_CONTEXT => true]);
    }

    /**
     * Returns whether transforms are currently requested immediately, or `null` if there’s no preference.
     */
    public function immediateTransforms(): ?bool
    {
        return Context::hasHidden(self::IMMEDIATE_TRANSFORMS_CONTEXT)
            ? Context::getHidden(self::IMMEDIATE_TRANSFORMS_CONTEXT) === true
            : null;
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
        $requestsByTransformer = [];

        foreach ($assets as $asset) {
            $assetDefinitions = $definitions instanceof Closure ? $definitions($asset) : $definitions;

            foreach ($this->preloadRequests($asset, $assetDefinitions) as $request) {
                $requestsByTransformer[$request->transformer->name][] = $request;
            }
        }

        foreach ($requestsByTransformer as $name => $requests) {
            $driver = $this->transformer($name);

            if ($driver instanceof PreloadsAssetTransforms) {
                $driver->preloadAssetTransforms($requests);
            }
        }
    }

    /** @return array<string, non-empty-list<string|Stringable>> */
    public function parameterRules(string $transformer): array
    {
        $parameterRules = $this->parameterRules;
        $driverRules = $this->transformer($transformer)->definition()->parameterRules;

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
    public function parameterFields(string $transformer): array
    {
        $definition = $this->transformer($transformer)->definition();
        $rules = $this->parameterRules($transformer);

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
    public function validateParameters(string $transformer, array $parameters): array
    {
        $rules = $this->parameterRules($transformer);
        $rules = Arr::only($rules, array_keys($parameters));
        $rules = array_map(fn (array $rules): array => ['nullable', ...$rules], $rules);

        if (Validator::make($parameters, $rules)->fails()) {
            throw new InvalidAssetTransformException('Invalid asset transform parameter value.');
        }

        ksort($parameters);

        return $parameters;
    }

    /**
     * Builds the `craft` driver from a transformer’s config.
     *
     * @param  array<string, mixed>  $config
     */
    protected function createCraftDriver(array $config): AssetTransformDriver
    {
        $outputDisk = $config['outputDisk'] ?? null;

        if ($outputDisk !== null && ! is_string($outputDisk)) {
            throw new InvalidAssetTransformException('The craft asset transform driver’s outputDisk must be a disk name.');
        }

        return $this->app->make(CraftAssetTransformDriver::class, [
            'outputDisk' => $outputDisk !== '' ? $outputDisk : null,
            'generateBeforePageLoad' => ($config['generateTransformsBeforePageLoad'] ?? false) === true,
        ]);
    }

    /**
     * Returns 6.0.0-beta.1 and beta.2’s deprecated `asset_transformer` key on a disk, following scoped disks to the disk they
     * wrap, or `null` if it isn’t set. A transformer name it names is returned as is. A driver name, or an array with
     * a `driver` key and inline settings, becomes a transformer config, with the `craft` driver’s beta setting
     * names mapped to the current ones.
     *
     * @param  string|array<string, mixed>  $disk  A disk name, or a scoped disk’s inline `disk` config.
     * @return string|array<string, mixed>|null
     */
    private function legacyDiskConfig(string|array $disk, int $depth = 0): string|array|null
    {
        $config = is_string($disk) ? ($this->config->get('filesystems.disks')[$disk] ?? null) : $disk;

        if (! is_array($config) || $depth > self::MAX_SCOPED_DEPTH) {
            return null;
        }

        if (! array_key_exists('asset_transformer', $config)) {
            $wrapped = $config['disk'] ?? null;

            return ($config['driver'] ?? null) === 'scoped' && (is_string($wrapped) || is_array($wrapped))
                ? $this->legacyDiskConfig($wrapped, $depth + 1)
                : null;
        }

        $value = $config['asset_transformer'];

        if (is_string($value) && $value !== '' && Cms::config()->assetTransformers->transformerConfig($value) !== null) {
            return $value;
        }

        $value = is_string($value) ? ['driver' => $value] : $value;

        if (! is_array($value)) {
            return null;
        }

        if (($value['driver'] ?? null) === 'craft') {
            $value = [
                'driver' => 'craft',
                'outputDisk' => $value['output_disk'] ?? null,
                'generateTransformsBeforePageLoad' => filter_var($value['generate_transforms_before_page_load'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ];
        }

        return $value;
    }

    private function forAsset(Asset $asset): AssetTransformer
    {
        return $this->forVolume($asset->getVolume());
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

            try {
                $this->transformer($transformer->name);
            } catch (AssetTransformException $exception) {
                throw new ($exception::class)("{$exception->getMessage()} It’s set by {$source}.", previous: $exception);
            }

            return $transformer;
        } catch (AssetTransformException $exception) {
            if (app()->hasDebugModeEnabled()) {
                throw $exception;
            }

            $message = "{$exception->getMessage()} Using the [{$fallback->name}] transformer instead.";

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
        if (! is_string($config) || $config === '') {
            throw new InvalidAssetTransformException("The asset transformer set by {$source} must be a transformer name.");
        }

        return new AssetTransformer($config);
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
                $transformer ??= $this->forAsset($asset);
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
            $driver = $this->transformer($transformer->name);
            $key = serialize($parameters);
            $this->validatedParameters[$driver] ??= [];
            $parameters = $this->validatedParameters[$driver][$key]
                ??= $this->validateParameters($transformer->name, $parameters);
        } else {
            $parameters = $this->validateParameters($transformer->name, $parameters);
        }

        return new AssetTransformRequest(
            asset: $asset,
            transformer: $transformer,
            parameters: $parameters,
            namedTransform: $this->namedTransform($definition),
            immediately: $this->immediateTransforms(),
        );
    }

    /**
     * Returns the named Image Transform a definition uses without overriding any of its parameters.
     */
    private function namedTransform(#[\SensitiveParameter] mixed $definition): ?ImageTransform
    {
        if (is_array($definition)) {
            return array_keys($definition) === ['transform'] ? $this->namedTransform($definition['transform']) : null;
        }

        if (! is_string($definition) && ! $definition instanceof ImageTransform) {
            return null;
        }

        $transform = ImageTransformHelper::normalizeTransform($definition);

        return ! empty($transform->handle) ? $transform : null;
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

        // Disks' deprecated asset_transformer keys have no transformer name to store parameters under, so they use the
        // driver's, as beta.1 and beta.2 did
        $key = str_starts_with($transformer->name, self::LEGACY_DISK_PREFIX)
            ? $this->getInstanceConfig($transformer->name)['driver'] ?? $transformer->name
            : $transformer->name;

        return $transform->getParameters($key);
    }
}
