<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Asset\AssetTransformDrivers;
use CraftCms\Cms\Asset\AssetTransformers as AssetTransformerService;
use CraftCms\Cms\Asset\Data\AssetTransformer;
use CraftCms\Cms\Asset\Exceptions\AssetTransformException;
use CraftCms\Cms\Mcp\Attributes\RequiresAdmin;
use CraftCms\Cms\Mcp\Attributes\RequiresAdminChanges;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Typecast;
use Illuminate\Validation\ValidationException;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Server\RequestContext;

/**
 * @since 6.0.0
 */
readonly class AssetTransformers
{
    public function __construct(
        private AssetTransformerService $assetTransformers,
        private AssetTransformDrivers $drivers,
    ) {}

    /** @return array{count: int, transformers: list<array<string, mixed>>} */
    #[RequiresAdmin]
    public function list(): array
    {
        $transformers = $this->assetTransformers
            ->getAllAssetTransformers()
            ->map($this->serialize(...))
            ->values();

        return [
            'count' => $transformers->count(),
            'transformers' => $transformers->all(),
        ];
    }

    /** @return array{transformer: array<string, mixed>} */
    #[RequiresAdmin]
    public function get(
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?string $handle = null,
    ): array {
        return ['transformer' => $this->serialize($this->find($uid, $handle))];
    }

    /**
     * @param  string  $driver  A registered Asset Transform driver handle, such as `craft`.
     * @param  array<string, mixed>  $settings  Driver settings. Settings the driver doesn’t define are dropped.
     * @return array{transformer: array<string, mixed>}
     */
    #[RequiresAdminChanges]
    public function create(
        string $name,
        string $handle,
        string $driver,
        #[Schema(type: 'object', additionalProperties: true)]
        array $settings = [],
    ): array {
        $transformer = new AssetTransformer([
            'name' => $name,
            'handle' => $handle,
            'driver' => $driver,
        ]);

        return $this->save($transformer, $settings);
    }

    /**
     * @param  string|null  $currentHandle  Existing Asset Transformer handle.
     * @param  string|null  $handle  New Asset Transformer handle.
     * @param  string|null  $driver  A registered Asset Transform driver handle. Changing it clears settings that aren’t supplied.
     * @param  array<string, mixed>|null  $settings  Driver settings, replacing the current settings. Settings the driver doesn’t define are dropped.
     * @return array{transformer: array<string, mixed>}
     */
    #[RequiresAdminChanges]
    public function update(
        RequestContext $context,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?string $currentHandle = null,
        ?string $name = null,
        ?string $handle = null,
        ?string $driver = null,
        #[Schema(type: 'object', additionalProperties: true)]
        ?array $settings = null,
    ): array {
        $transformer = $this->find($uid, $currentHandle);
        $request = $context->getRequest();
        assert($request instanceof CallToolRequest);
        $supplied = array_intersect_key([
            'name' => $name,
            'handle' => $handle,
            'driver' => $driver,
        ], $request->arguments);

        if (isset($supplied['driver']) && $supplied['driver'] !== $transformer->driver) {
            $transformer->settings = [];
        }

        Typecast::configure($transformer, $supplied);

        return $this->save($transformer, $settings ?? $transformer->settings);
    }

    /** @return array{deleted: true} */
    #[RequiresAdminChanges]
    public function delete(
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?string $handle = null,
    ): array {
        $transformer = $this->find($uid, $handle);

        try {
            $this->assetTransformers->deleteAssetTransformer($transformer);
        } catch (AssetTransformException $exception) {
            throw new ToolCallException($exception->getMessage(), previous: $exception);
        }

        return ['deleted' => true];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array{transformer: array<string, mixed>}
     */
    private function save(AssetTransformer $transformer, array $settings): array
    {
        if (! array_key_exists($transformer->driver, $this->drivers->definitions())) {
            $available = implode(', ', array_keys($this->drivers->definitions()));

            throw new ToolCallException("Unknown Asset Transform driver [{$transformer->driver}]. Available drivers: $available.");
        }

        try {
            $transformer->settings = $this->drivers->settings($transformer->driver, $settings);
        } catch (ValidationException $exception) {
            throw new ToolCallException(implode("\n", Arr::flatten($exception->errors())), previous: $exception);
        }

        if (! $this->assetTransformers->saveAssetTransformer($transformer)) {
            throw new ToolCallException(implode("\n", $transformer->errors()->all()) ?: 'Asset Transformer could not be saved.');
        }

        return ['transformer' => $this->serialize($transformer)];
    }

    private function find(?string $uid, ?string $handle): AssetTransformer
    {
        if (count(Arr::whereNotNull([$uid, $handle])) !== 1) {
            throw new ToolCallException('Provide exactly one of: uid, handle.');
        }

        $transformer = $uid !== null
            ? $this->assetTransformers->getAssetTransformerByUid($uid)
            : $this->assetTransformers->getAssetTransformerByHandle($handle);

        if ($transformer === null) {
            throw new ToolCallException('Asset Transformer not found.');
        }

        return clone $transformer;
    }

    /** @return array<string, mixed> */
    private function serialize(AssetTransformer $transformer): array
    {
        return [
            'uid' => $transformer->uid,
            ...$transformer->getConfig(),
        ];
    }
}
