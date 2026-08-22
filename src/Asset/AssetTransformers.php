<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset;

use CraftCms\Cms\Asset\Contracts\PreloadsAssetTransforms;
use CraftCms\Cms\Asset\Data\AssetTransformer;
use CraftCms\Cms\Asset\Data\AssetTransformRequest;
use CraftCms\Cms\Asset\Data\AssetTransformResult;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Events\AssetTransformerDeleting;
use CraftCms\Cms\Asset\Events\AssetTransformerUpdating;
use CraftCms\Cms\Asset\Exceptions\AssetTransformerNotFoundException;
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

#[Singleton]
class AssetTransformers
{
    /** @var array<string, non-empty-list<string|Stringable>> */
    private readonly array $operations;

    /** @var Collection<string, AssetTransformer>|null */
    private ?Collection $transformers = null;

    public function __construct(
        private readonly ProjectConfig $projectConfig,
        private readonly AssetTransformDrivers $drivers,
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

    /** @return Collection<string, AssetTransformer> */
    public function getAllAssetTransformers(): Collection
    {
        $this->ensureCraftAssetTransformer();

        return $this->assetTransformers();
    }

    public function getAssetTransformerByHandle(string $handle): ?AssetTransformer
    {
        $handle = Env::parse($handle);

        if (! is_string($handle) || $handle === '') {
            return null;
        }

        return $this->getAllAssetTransformers()->firstWhere('handle', $handle);
    }

    public function getAssetTransformerByUid(string $uid): ?AssetTransformer
    {
        return $this->getAllAssetTransformers()->get($uid);
    }

    public function getDefaultAssetTransformer(): AssetTransformer
    {
        return $this->resolve(Cms::config()->defaultAssetTransformer);
    }

    public function resolve(string $handle): AssetTransformer
    {
        $parsedHandle = Env::parse($handle);

        if (! is_string($parsedHandle) || $parsedHandle === '') {
            throw new AssetTransformerNotFoundException("Asset Transformer [{$handle}] is not configured.");
        }

        return $this->getAssetTransformerByHandle($parsedHandle)
            ?? throw new AssetTransformerNotFoundException("Asset Transformer [{$parsedHandle}] is not configured.");
    }

    public function ensureCraftAssetTransformer(): void
    {
        $craft = $this->assetTransformers()->firstWhere('handle', 'craft');

        if ($craft !== null && $craft->driver === 'craft') {
            return;
        }

        if ($craft !== null) {
            throw new AssetTransformException('The reserved [craft] Asset Transformer must use the [craft] driver.');
        }

        $this->saveAssetTransformer(new AssetTransformer([
            'name' => 'Craft',
            'handle' => 'craft',
            'driver' => 'craft',
            'settings' => [
                'filesystem' => null,
                'subpath' => null,
            ],
        ]), false);
    }

    public function saveAssetTransformer(AssetTransformer $transformer, bool $runValidation = true): bool
    {
        $existing = $transformer->uid
            ? $this->getAssetTransformerByUid($transformer->uid)
            : null;

        if ($existing?->handle === 'craft') {
            $transformer->name = 'Craft';
            $transformer->handle = 'craft';
            $transformer->driver = 'craft';
        }

        if ($runValidation) {
            $this->validateAssetTransformer($transformer);
        }

        if ($transformer->errors()->isNotEmpty()) {
            return false;
        }

        $transformer->uid ??= Str::uuid()->toString();
        $oldHandle = $existing?->handle;
        $config = $transformer->getConfig();
        $config['settings'] = ProjectConfigHelper::packAssociativeArrays($config['settings']);

        $this->projectConfig->set(
            ProjectConfig::PATH_ASSET_TRANSFORMERS.'.'.$transformer->uid,
            $config,
            "Save the “{$transformer->handle}” Asset Transformer",
        );

        if ($oldHandle && $oldHandle !== $transformer->handle) {
            $this->rewriteHandleReferences($oldHandle, $transformer->handle);
        }

        $this->reset();

        return true;
    }

    public function deleteAssetTransformer(AssetTransformer $transformer): bool
    {
        if ($transformer->handle === 'craft') {
            throw new AssetTransformException('The reserved [craft] Asset Transformer cannot be deleted.');
        }

        $this->ensureNotReferenced($transformer);
        $this->projectConfig->remove(
            ProjectConfig::PATH_ASSET_TRANSFORMERS.'.'.$transformer->uid,
            "Delete the “{$transformer->handle}” Asset Transformer",
        );
        $this->reset();

        return true;
    }

    public function handleChangedAssetTransformer(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0] ?? null;

        if (! is_string($uid) || ! is_array($event->newValue)) {
            $this->reset();

            return;
        }

        $newTransformer = $this->createAssetTransformer($uid, $event->newValue);
        $oldTransformer = is_array($event->oldValue)
            ? $this->createAssetTransformer($uid, $event->oldValue)
            : null;

        if (
            ($oldTransformer?->handle === 'craft' && $newTransformer->handle !== 'craft')
            || ($newTransformer->handle === 'craft' && ($newTransformer->name !== 'Craft' || $newTransformer->driver !== 'craft'))
        ) {
            throw new AssetTransformException('The reserved [craft] Asset Transformer’s name, handle, and driver cannot be changed.');
        }

        if (
            $oldTransformer !== null
            && ($oldTransformer->driver !== $newTransformer->driver || $oldTransformer->settings !== $newTransformer->settings)
        ) {
            event(new AssetTransformerUpdating($oldTransformer, $newTransformer));
        }

        $this->reset();
    }

    public function handleDeletedAssetTransformer(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0] ?? null;

        if (! is_string($uid) || ! is_array($event->oldValue)) {
            $this->reset();

            return;
        }

        $transformer = $this->createAssetTransformer($uid, $event->oldValue);

        if ($transformer->handle === 'craft') {
            throw new AssetTransformException('The reserved [craft] Asset Transformer cannot be deleted.');
        }

        $this->ensureNotReferenced($transformer);
        event(new AssetTransformerDeleting($transformer));
        $this->reset();
    }

    public function handleAssetTransformersRemoved(): void
    {
        throw new AssetTransformException('The Asset Transformers Project Config section cannot be removed.');
    }

    public function handleFilesystemRenamed(FilesystemRenamed $event): void
    {
        $oldHandle = $event->filesystem->oldHandle;
        $newHandle = $event->filesystem->handle;

        if (! $oldHandle || ! $newHandle || $oldHandle === $newHandle) {
            return;
        }

        $changed = false;

        foreach ($this->getAllAssetTransformers() as $transformer) {
            if ($transformer->driver !== 'craft' || ($transformer->settings['filesystem'] ?? null) !== $oldHandle) {
                continue;
            }

            $this->projectConfig->set(
                ProjectConfig::PATH_ASSET_TRANSFORMERS.'.'.$transformer->uid.'.settings.filesystem',
                $newHandle,
                "Update the “{$transformer->handle}” Asset Transformer's output filesystem",
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

        return $this->drivers->driver($request->transformer->driver)->transform($request);
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
                $requestsByDriver[$request->transformer->driver][] = $request;
            }
        }

        foreach ($requestsByDriver as $driverHandle => $requests) {
            $driver = $this->drivers->driver($driverHandle);

            if ($driver instanceof PreloadsAssetTransforms) {
                $driver->preloadAssetTransforms($requests);
            }
        }
    }

    /** @return array<string, non-empty-list<string|Stringable>> */
    public function operationRules(AssetTransformer $transformer): array
    {
        $operations = $this->operations;

        foreach ($this->drivers->driver($transformer->driver)->definition()->operations as $handle => $rules) {
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
    public function operationFields(AssetTransformer $transformer): array
    {
        $definition = $this->drivers->driver($transformer->driver)->definition();
        $rules = $this->operationRules($transformer);

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
    public function validateOperations(AssetTransformer $transformer, array $operations): array
    {
        $rules = $this->operationRules($transformer);
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
        $this->transformers = null;
    }

    /** @return Collection<string, AssetTransformer> */
    private function assetTransformers(): Collection
    {
        if ($this->transformers !== null) {
            return $this->transformers;
        }

        $configs = $this->projectConfig->get(ProjectConfig::PATH_ASSET_TRANSFORMERS);

        if (! is_array($configs)) {
            $configs = [];
        }

        return $this->transformers = collect($configs)
            ->filter(fn (mixed $config, mixed $uid): bool => is_string($uid) && is_array($config))
            ->map(fn (array $config, string $uid): AssetTransformer => $this->createAssetTransformer($uid, $config));
    }

    /** @param array<string, mixed> $config */
    private function createAssetTransformer(string $uid, array $config): AssetTransformer
    {
        $settings = ProjectConfigHelper::unpackAssociativeArrays($config['settings'] ?? []);

        return new AssetTransformer([
            'uid' => $uid,
            'name' => $config['name'] ?? null,
            'handle' => $config['handle'] ?? null,
            'driver' => $config['driver'] ?? null,
            'settings' => $settings,
        ]);
    }

    private function validateAssetTransformer(AssetTransformer $transformer): void
    {
        $transformer->validate();

        if ($transformer->driver && ! $this->drivers->has($transformer->driver)) {
            $transformer->errors()->add('driver', 'The selected Asset Transform driver is unavailable.');
        }

        $duplicate = $this->getAllAssetTransformers()
            ->first(fn (AssetTransformer $existing): bool => $existing->handle === $transformer->handle && $existing->uid !== $transformer->uid);

        if ($duplicate !== null) {
            $transformer->errors()->add('handle', 'The Asset Transformer handle has already been taken.');
        }

        if ($transformer->handle === 'craft' && $transformer->driver !== 'craft') {
            $transformer->errors()->add('driver', 'The reserved [craft] Asset Transformer must use the [craft] driver.');
        }
    }

    private function ensureNotReferenced(AssetTransformer $transformer): void
    {
        $default = Env::parse(Cms::config()->defaultAssetTransformer);

        if ($default === $transformer->handle) {
            throw new AssetTransformException("Asset Transformer [{$transformer->handle}] is the configured default.");
        }

        $volumes = $this->projectConfig->get(ProjectConfig::PATH_VOLUMES);

        if (! is_array($volumes)) {
            return;
        }

        foreach ($volumes as $volume) {
            $reference = is_array($volume) ? ($volume['assetTransformer'] ?? null) : null;

            if (is_string($reference) && Env::parse($reference) === $transformer->handle) {
                throw new AssetTransformException("Asset Transformer [{$transformer->handle}] is referenced by a volume.");
            }
        }
    }

    private function rewriteHandleReferences(string $oldHandle, string $newHandle): void
    {
        if (Cms::config()->defaultAssetTransformer === $oldHandle) {
            Cms::config()->defaultAssetTransformer = $newHandle;
        }

        $volumes = app(Volumes::class);

        foreach ($volumes->getAllVolumes() as $volume) {
            if ($volume->getAssetTransformerHandle(false) !== $oldHandle) {
                continue;
            }

            $volume->assetTransformer = $newHandle;
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
                'transformer' => $referenceRequest->transformer->handle,
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
        $volumeTransformer = $asset->getVolume()->getAssetTransformerHandle(false);
        $transformerHandle = $this->transformerOverride($definition)
            ?? ($volumeTransformer !== null && $volumeTransformer !== '' ? $volumeTransformer : null)
            ?? Cms::config()->defaultAssetTransformer;
        $transformer = $this->resolve($transformerHandle);

        try {
            $operations = $this->normalizeDefinition($definition, $transformer);
        } catch (ImageTransformException|InvalidArgumentException $exception) {
            throw new InvalidAssetTransformException($exception->getMessage(), previous: $exception);
        }

        return new AssetTransformRequest(
            asset: $asset,
            transformer: $transformer,
            operations: $this->validateOperations($transformer, $operations),
            immediately: $immediately ?? Cms::config()->generateTransformsBeforePageLoad,
        );
    }

    private function transformerOverride(mixed $definition): ?string
    {
        if (! is_array($definition)) {
            return null;
        }

        if (array_key_exists('transformer', $definition)) {
            $handle = $definition['transformer'];

            if (! is_string($handle) || $handle === '') {
                throw new AssetTransformerNotFoundException('The selected Asset Transformer is invalid.');
            }

            return $handle;
        }

        return array_key_exists('transform', $definition)
            ? $this->transformerOverride($definition['transform'])
            : null;
    }

    /** @return array<string, mixed> */
    private function normalizeDefinition(mixed $definition, AssetTransformer $transformer): array
    {
        if (is_array($definition)) {
            unset($definition['transformer']);

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
            throw new InvalidAssetTransformException('An Asset Transform definition must be an array, object, or named transform handle.');
        }

        return $transform->getOperations($transformer->uid);
    }
}
