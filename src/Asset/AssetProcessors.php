<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset;

use CraftCms\Cms\Asset\Contracts\PreloadsAssetTransforms;
use CraftCms\Cms\Asset\Data\AssetProcessor;
use CraftCms\Cms\Asset\Data\AssetTransformRequest;
use CraftCms\Cms\Asset\Data\AssetTransformResult;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Events\AssetProcessorDeleting;
use CraftCms\Cms\Asset\Events\AssetProcessorUpdating;
use CraftCms\Cms\Asset\Exceptions\AssetProcessorNotFoundException;
use CraftCms\Cms\Asset\Exceptions\AssetTransformException;
use CraftCms\Cms\Asset\Exceptions\ImageTransformException;
use CraftCms\Cms\Asset\Exceptions\InvalidAssetTransformException;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Filesystem\Events\FilesystemRenamed;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Image\Enums\ImageTransformFormat;
use CraftCms\Cms\Image\Enums\ImageTransformInterlace;
use CraftCms\Cms\Image\Enums\ImageTransformMode;
use CraftCms\Cms\Image\Enums\ImageTransformPosition;
use CraftCms\Cms\Image\Events\AssetTransformsInvalidating;
use CraftCms\Cms\Image\ImageTransformHelper;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Str;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Stringable;

use function CraftCms\Cms\t;

#[Singleton]
class AssetProcessors
{
    /** @var array<string, non-empty-list<string|Stringable>> */
    private readonly array $operations;

    /** @var Collection<string, AssetProcessor>|null */
    private ?Collection $processors = null;

    public function __construct(
        private readonly ProjectConfig $projectConfig,
        private readonly AssetProcessorDrivers $processorDrivers,
    ) {
        $this->operations = [
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

    /** @return Collection<string, AssetProcessor> */
    public function getAllAssetProcessors(): Collection
    {
        $this->ensureCraftAssetProcessor();

        return $this->assetProcessors();
    }

    public function getAssetProcessorByHandle(string $handle): ?AssetProcessor
    {
        $handle = Env::parse($handle);

        if (! is_string($handle) || $handle === '') {
            return null;
        }

        return $this->getAllAssetProcessors()->firstWhere('handle', $handle);
    }

    public function getAssetProcessorByUid(string $uid): ?AssetProcessor
    {
        return $this->getAllAssetProcessors()->get($uid);
    }

    public function getDefaultAssetProcessor(): AssetProcessor
    {
        return $this->resolve(Cms::config()->defaultAssetProcessor);
    }

    public function resolve(string $handle): AssetProcessor
    {
        $parsedHandle = Env::parse($handle);

        if (! is_string($parsedHandle) || $parsedHandle === '') {
            throw new AssetProcessorNotFoundException("Asset Processor [{$handle}] is not configured.");
        }

        return $this->getAssetProcessorByHandle($parsedHandle)
            ?? throw new AssetProcessorNotFoundException("Asset Processor [{$parsedHandle}] is not configured.");
    }

    public function ensureCraftAssetProcessor(): void
    {
        $craft = $this->assetProcessors()->firstWhere('handle', 'craft');

        if ($craft !== null && $craft->driver === 'craft') {
            return;
        }

        if ($craft !== null) {
            throw new AssetTransformException('The reserved [craft] Asset Processor must use the [craft] driver.');
        }

        $this->saveAssetProcessor(new AssetProcessor([
            'name' => 'Craft',
            'handle' => 'craft',
            'driver' => 'craft',
            'settings' => [
                'filesystem' => null,
                'subpath' => null,
            ],
        ]), false);
    }

    public function saveAssetProcessor(AssetProcessor $processor, bool $runValidation = true): bool
    {
        $existing = $processor->uid
            ? $this->getAssetProcessorByUid($processor->uid)
            : null;

        if ($existing?->handle === 'craft') {
            $processor->name = 'Craft';
            $processor->handle = 'craft';
            $processor->driver = 'craft';
        }

        if ($runValidation) {
            $this->validateAssetProcessor($processor);
        }

        if ($processor->errors()->isNotEmpty()) {
            return false;
        }

        $processor->uid ??= Str::uuid()->toString();
        $oldHandle = $existing?->handle;
        $config = $processor->getConfig();
        $config['settings'] = ProjectConfigHelper::packAssociativeArrays($config['settings']);

        $this->projectConfig->set(
            ProjectConfig::PATH_ASSET_PROCESSORS.'.'.$processor->uid,
            $config,
            "Save the “{$processor->handle}” Asset Processor",
        );

        if ($oldHandle && $oldHandle !== $processor->handle) {
            $this->rewriteHandleReferences($oldHandle, $processor->handle);
        }

        $this->reset();

        return true;
    }

    public function deleteAssetProcessor(AssetProcessor $processor): bool
    {
        if ($processor->handle === 'craft') {
            throw new AssetTransformException('The reserved [craft] Asset Processor cannot be deleted.');
        }

        $this->ensureNotReferenced($processor);
        $this->projectConfig->remove(
            ProjectConfig::PATH_ASSET_PROCESSORS.'.'.$processor->uid,
            "Delete the “{$processor->handle}” Asset Processor",
        );
        $this->reset();

        return true;
    }

    public function handleChangedAssetProcessor(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0] ?? null;

        if (! is_string($uid) || ! is_array($event->newValue)) {
            $this->reset();

            return;
        }

        $newProcessor = $this->createAssetProcessor($uid, $event->newValue);
        $oldProcessor = is_array($event->oldValue)
            ? $this->createAssetProcessor($uid, $event->oldValue)
            : null;

        if (
            ($oldProcessor?->handle === 'craft' && $newProcessor->handle !== 'craft')
            || ($newProcessor->handle === 'craft' && ($newProcessor->name !== 'Craft' || $newProcessor->driver !== 'craft'))
        ) {
            throw new AssetTransformException('The reserved [craft] Asset Processor’s name, handle, and driver cannot be changed.');
        }

        if (
            $oldProcessor !== null
            && ($oldProcessor->driver !== $newProcessor->driver || $oldProcessor->settings !== $newProcessor->settings)
        ) {
            event(new AssetProcessorUpdating($oldProcessor, $newProcessor));
        }

        $this->reset();
    }

    public function handleDeletedAssetProcessor(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0] ?? null;

        if (! is_string($uid) || ! is_array($event->oldValue)) {
            $this->reset();

            return;
        }

        $processor = $this->createAssetProcessor($uid, $event->oldValue);

        if ($processor->handle === 'craft') {
            throw new AssetTransformException('The reserved [craft] Asset Processor cannot be deleted.');
        }

        $this->ensureNotReferenced($processor);
        event(new AssetProcessorDeleting($processor));
        $this->reset();
    }

    public function handleAssetProcessorsRemoved(): void
    {
        throw new AssetTransformException('The Asset Processors Project Config section cannot be removed.');
    }

    public function handleFilesystemRenamed(FilesystemRenamed $event): void
    {
        $oldHandle = $event->filesystem->oldHandle;
        $newHandle = $event->filesystem->handle;

        if (! $oldHandle || ! $newHandle || $oldHandle === $newHandle) {
            return;
        }

        $changed = false;

        foreach ($this->getAllAssetProcessors() as $processor) {
            if ($processor->driver !== 'craft' || ($processor->settings['filesystem'] ?? null) !== $oldHandle) {
                continue;
            }

            $this->projectConfig->set(
                ProjectConfig::PATH_ASSET_PROCESSORS.'.'.$processor->uid.'.settings.filesystem',
                $newHandle,
                "Update the “{$processor->handle}” Asset Processor's output filesystem",
            );
            $changed = true;
        }

        if ($changed) {
            $this->reset();
        }
    }

    public function transform(Asset $asset, #[\SensitiveParameter] mixed $definition, ?bool $immediately = null): AssetTransformResult
    {
        $request = $this->request($asset, $definition, $immediately);

        return $this->processorDrivers->driver($request->processor->driver)->transform($request);
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
                $requestsByDriver[$request->processor->driver][] = $request;
            }
        }

        foreach ($requestsByDriver as $driverHandle => $requests) {
            $driver = $this->processorDrivers->driver($driverHandle);

            if ($driver instanceof PreloadsAssetTransforms) {
                $driver->preloadAssetTransforms($requests);
            }
        }
    }

    /** @return array<string, non-empty-list<string|Stringable>> */
    public function operationRules(AssetProcessor $processor): array
    {
        $operations = $this->operations;

        foreach ($this->processorDrivers->driver($processor->driver)->definition()->operations as $handle => $rules) {
            if (! is_string($handle) || $handle === '') {
                throw new InvalidAssetTransformException('Asset Transform operation handles must be non-empty strings.');
            }

            if (isset($operations[$handle]) && $operations[$handle] != $rules) {
                throw new InvalidAssetTransformException("Asset Transform operation [{$handle}] conflicts with a core operation.");
            }

            $operations[$handle] = $rules;
        }

        return $operations;
    }

    /** @return array<string, non-empty-list<string|Stringable>> */
    public function coreOperationRules(): array
    {
        return $this->operations;
    }

    /** @return array<string, Field> */
    public function operationFields(AssetProcessor $processor): array
    {
        $definition = $this->processorDrivers->driver($processor->driver)->definition();
        $rules = $this->operationRules($processor);

        foreach ($definition->operationFields as $handle => $field) {
            $path = $field->getControl()?->path();
            $path = is_array($path) && count($path) === 1 ? $path[0] : $path;

            if (! is_string($handle) || ! isset($rules[$handle]) || $path !== $handle) {
                throw new InvalidAssetTransformException('Asset Transform operation fields must match a declared operation handle.');
            }
        }

        return $definition->operationFields;
    }

    /**
     * @param  array<string, mixed>  $operations
     * @return array<string, mixed>
     */
    public function validateOperations(AssetProcessor $processor, array $operations): array
    {
        $rules = $this->operationRules($processor);
        $operations = Arr::only($operations, array_keys($rules));
        $rules = Arr::only($rules, array_keys($operations));
        $rules = array_map(fn (array $rules): array => ['nullable', ...$rules], $rules);

        if (Validator::make($operations, $rules)->fails()) {
            throw new InvalidAssetTransformException('Invalid Asset Transform operation value.');
        }

        ksort($operations);

        return $operations;
    }

    public function reset(): void
    {
        $this->processors = null;
    }

    /** @return Collection<string, AssetProcessor> */
    private function assetProcessors(): Collection
    {
        if ($this->processors !== null) {
            return $this->processors;
        }

        $configs = $this->projectConfig->get(ProjectConfig::PATH_ASSET_PROCESSORS);

        if (! is_array($configs)) {
            $configs = [];
        }

        return $this->processors = collect($configs)
            ->filter(fn (mixed $config, mixed $uid): bool => is_string($uid) && is_array($config))
            ->map(fn (array $config, string $uid): AssetProcessor => $this->createAssetProcessor($uid, $config));
    }

    /** @param array<string, mixed> $config */
    private function createAssetProcessor(string $uid, array $config): AssetProcessor
    {
        $settings = ProjectConfigHelper::unpackAssociativeArrays($config['settings'] ?? []);

        return new AssetProcessor([
            'uid' => $uid,
            'name' => $config['name'] ?? '',
            'handle' => $config['handle'] ?? '',
            'driver' => $config['driver'] ?? '',
            'settings' => $settings,
        ]);
    }

    private function validateAssetProcessor(AssetProcessor $processor): void
    {
        $processor->validate();

        if ($processor->driver && ! $this->processorDrivers->has($processor->driver)) {
            $processor->errors()->add('driver', t('The selected Asset Processor driver is unavailable.'));
        }

        $duplicate = $this->getAllAssetProcessors()
            ->first(fn (AssetProcessor $existing): bool => $existing->handle === $processor->handle && $existing->uid !== $processor->uid);

        if ($duplicate !== null) {
            $processor->errors()->add('handle', t('The Asset Processor handle has already been taken.'));
        }

        if ($processor->handle === 'craft' && $processor->driver !== 'craft') {
            $processor->errors()->add('driver', t('The reserved [craft] Asset Processor must use the [craft] driver.'));
        }
    }

    private function ensureNotReferenced(AssetProcessor $processor): void
    {
        $default = Env::parse(Cms::config()->defaultAssetProcessor);

        if ($default === $processor->handle) {
            throw new AssetTransformException("Asset Processor [{$processor->handle}] is the configured default.");
        }

        $volumes = $this->projectConfig->get(ProjectConfig::PATH_VOLUMES);

        if (! is_array($volumes)) {
            return;
        }

        foreach ($volumes as $volume) {
            $reference = is_array($volume) ? ($volume['assetProcessor'] ?? null) : null;

            if (is_string($reference) && Env::parse($reference) === $processor->handle) {
                throw new AssetTransformException("Asset Processor [{$processor->handle}] is referenced by a volume.");
            }
        }
    }

    private function rewriteHandleReferences(string $oldHandle, string $newHandle): void
    {
        if (Cms::config()->defaultAssetProcessor === $oldHandle) {
            Cms::config()->defaultAssetProcessor = $newHandle;
        }

        $volumes = app(Volumes::class);

        foreach ($volumes->getAllVolumes() as $volume) {
            if ($volume->getAssetProcessorHandle(false) !== $oldHandle) {
                continue;
            }

            $volume->assetProcessor = $newHandle;
            $volumes->saveVolume($volume);
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
                'processor' => $referenceRequest->processor->handle,
                ...$operations,
            ]);
        }

        return $requests;
    }

    private function request(
        Asset $asset,
        #[\SensitiveParameter] mixed $definition,
        ?bool $immediately = null,
    ): AssetTransformRequest {
        $volumeProcessor = $asset->getVolume()->getAssetProcessorHandle(false);
        $processorHandle = $this->processorOverride($definition)
            ?? ($volumeProcessor !== null && $volumeProcessor !== '' ? $volumeProcessor : null)
            ?? Cms::config()->defaultAssetProcessor;
        $processor = $this->resolve($processorHandle);

        try {
            $operations = $this->normalizeDefinition($definition, $processor);
        } catch (ImageTransformException|InvalidArgumentException $exception) {
            throw new InvalidAssetTransformException($exception->getMessage(), previous: $exception);
        }

        return new AssetTransformRequest(
            asset: $asset,
            processor: $processor,
            operations: $this->validateOperations($processor, $operations),
            immediately: $immediately ?? Cms::config()->generateTransformsBeforePageLoad,
        );
    }

    private function processorOverride(mixed $definition): ?string
    {
        if (! is_array($definition)) {
            return null;
        }

        if (array_key_exists('processor', $definition)) {
            $handle = $definition['processor'];

            if (! is_string($handle) || $handle === '') {
                throw new AssetProcessorNotFoundException('The selected Asset Processor is invalid.');
            }

            return $handle;
        }

        return array_key_exists('transform', $definition)
            ? $this->processorOverride($definition['transform'])
            : null;
    }

    /** @return array<string, mixed> */
    private function normalizeDefinition(mixed $definition, AssetProcessor $processor): array
    {
        if (is_array($definition)) {
            unset($definition['processor']);

            if (! array_key_exists('transform', $definition)) {
                return $definition;
            }

            return [
                ...$this->normalizeDefinition(Arr::pull($definition, 'transform'), $processor),
                ...$definition,
            ];
        }

        $transform = ImageTransformHelper::normalizeTransform($definition);

        if ($transform === null) {
            throw new InvalidAssetTransformException('An Asset Transform definition must be an array, object, or named transform handle.');
        }

        return $transform->getOperations($processor->uid);
    }
}
