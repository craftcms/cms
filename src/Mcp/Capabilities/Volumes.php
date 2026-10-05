<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Volumes as VolumeService;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\Mcp\Attributes\RequiresAdmin;
use CraftCms\Cms\Mcp\Attributes\RequiresAdminChanges;
use CraftCms\Cms\Mcp\Schema\FieldLayoutConfig;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Typecast;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\RequestContext;

/**
 * @since 6.0.0
 */
readonly class Volumes
{
    public function __construct(
        private FieldLayoutConfig $fieldLayouts,
        private VolumeService $volumes,
    ) {}

    /** @return array{count: int, volumes: list<array<string, mixed>>} */
    #[McpTool(
        name: 'volumes.list',
        description: 'Lists Craft CMS asset volumes.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresAdmin]
    public function list(): array
    {
        $volumes = $this->volumes
            ->getAllVolumes()
            ->map($this->serializeSummary(...))
            ->values();

        return [
            'count' => $volumes->count(),
            'volumes' => $volumes->all(),
        ];
    }

    /**
     * @param  int|null  $id  Volume ID.
     * @param  string|null  $uid  Volume UID.
     * @param  string|null  $handle  Volume handle.
     * @return array{volume: array<string, mixed>}
     */
    #[McpTool(
        name: 'volumes.get',
        description: 'Gets a Craft CMS asset volume by ID, UID, or handle.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresAdmin]
    public function get(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?string $handle = null,
    ): array {
        $volume = $this->find($id, $uid, $handle);

        if (! $volume) {
            throw new ToolCallException('Volume not found.');
        }

        return ['volume' => $this->serialize($volume)];
    }

    /**
     * @param  string  $name  Volume name.
     * @param  string  $handle  Volume handle.
     * @param  string  $fsHandle  Laravel filesystem disk name configured in filesystems.disks.
     * @param  string|null  $subpath  Asset subpath within the disk.
     * @param  string|null  $assetTransformer  Asset transformer handle. Uses Craft's default when null.
     * @param  TranslationMethod  $titleTranslationMethod  Asset title translation method.
     * @param  string|null  $titleTranslationKeyFormat  Custom asset title translation key format.
     * @param  TranslationMethod  $altTranslationMethod  Asset alternative-text translation method.
     * @param  string|null  $altTranslationKeyFormat  Custom asset alternative-text translation key format.
     * @param  bool  $hasUrls  Whether assets in the volume have public URLs.
     * @param  array<string, mixed>|null  $fieldLayout  Native Craft field layout config.
     * @return array{volume: array<string, mixed>}
     */
    #[McpTool(name: 'volumes.create', description: 'Creates a Craft CMS asset volume.')]
    #[RequiresAdminChanges]
    public function create(
        string $name,
        string $handle,
        string $fsHandle,
        ?string $subpath = null,
        ?string $assetTransformer = null,
        TranslationMethod $titleTranslationMethod = TranslationMethod::Site,
        ?string $titleTranslationKeyFormat = null,
        TranslationMethod $altTranslationMethod = TranslationMethod::None,
        ?string $altTranslationKeyFormat = null,
        bool $hasUrls = false,
        #[Schema(definition: FieldLayoutConfig::Schema)]
        ?array $fieldLayout = null,
    ): array {
        $volume = new Volume([
            'name' => $name,
            'handle' => $handle,
            'fsHandle' => $fsHandle,
            'subpath' => $subpath,
            'assetTransformer' => $assetTransformer,
            'titleTranslationMethod' => $titleTranslationMethod,
            'titleTranslationKeyFormat' => $titleTranslationKeyFormat,
            'altTranslationMethod' => $altTranslationMethod,
            'altTranslationKeyFormat' => $altTranslationKeyFormat,
            'hasUrls' => $hasUrls,
        ]);

        if ($fieldLayout !== null) {
            $volume->setFieldLayout($this->fieldLayouts->make($fieldLayout, Asset::class));
        }

        if (! $this->volumes->saveVolume($volume)) {
            throw new ToolCallException($this->validationErrors($volume));
        }

        return ['volume' => $this->serialize($this->saved($volume))];
    }

    /**
     * @param  int|null  $id  Volume ID.
     * @param  string|null  $uid  Volume UID.
     * @param  string|null  $currentHandle  Existing volume handle.
     * @param  string|null  $name  Volume name.
     * @param  string|null  $handle  New volume handle.
     * @param  string|null  $fsHandle  Laravel filesystem disk name configured in filesystems.disks.
     * @param  string|null  $subpath  Asset subpath within the disk.
     * @param  string|null  $assetTransformer  Asset transformer handle. Uses Craft's default when null.
     * @param  TranslationMethod  $titleTranslationMethod  Asset title translation method.
     * @param  string|null  $titleTranslationKeyFormat  Custom asset title translation key format.
     * @param  TranslationMethod  $altTranslationMethod  Asset alternative-text translation method.
     * @param  string|null  $altTranslationKeyFormat  Custom asset alternative-text translation key format.
     * @param  bool  $hasUrls  Whether assets in the volume have public URLs.
     * @param  array<string, mixed>|null  $fieldLayout  Native Craft field layout config.
     * @return array{volume: array<string, mixed>}
     */
    #[McpTool(
        name: 'volumes.update',
        description: 'Updates a Craft CMS asset volume.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    #[RequiresAdminChanges]
    public function update(
        RequestContext $context,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?string $currentHandle = null,
        ?string $name = null,
        ?string $handle = null,
        ?string $fsHandle = null,
        ?string $subpath = null,
        ?string $assetTransformer = null,
        TranslationMethod $titleTranslationMethod = TranslationMethod::Site,
        ?string $titleTranslationKeyFormat = null,
        TranslationMethod $altTranslationMethod = TranslationMethod::None,
        ?string $altTranslationKeyFormat = null,
        bool $hasUrls = false,
        #[Schema(definition: FieldLayoutConfig::NullableSchema)]
        ?array $fieldLayout = null,
    ): array {
        $volume = $this->find($id, $uid, $currentHandle);

        if (! $volume) {
            throw new ToolCallException('Volume not found.');
        }

        $request = $context->getRequest();
        assert($request instanceof CallToolRequest);

        Typecast::configure($volume, array_intersect_key([
            'name' => $name,
            'handle' => $handle,
            'fsHandle' => $fsHandle,
            'subpath' => $subpath,
            'assetTransformer' => $assetTransformer,
            'titleTranslationMethod' => $titleTranslationMethod,
            'titleTranslationKeyFormat' => $titleTranslationKeyFormat,
            'altTranslationMethod' => $altTranslationMethod,
            'altTranslationKeyFormat' => $altTranslationKeyFormat,
            'hasUrls' => $hasUrls,
        ], $request->arguments));

        if (array_key_exists('fieldLayout', $request->arguments)) {
            $volume->setFieldLayout($this->fieldLayouts->make(
                $fieldLayout ?? [],
                Asset::class,
                $volume->getFieldLayout(),
            ));
        }

        if (! $this->volumes->saveVolume($volume)) {
            throw new ToolCallException($this->validationErrors($volume));
        }

        return ['volume' => $this->serialize($this->saved($volume))];
    }

    /**
     * @param  int|null  $id  Volume ID.
     * @param  string|null  $uid  Volume UID.
     * @param  string|null  $handle  Volume handle.
     * @return array{deleted: true}
     */
    #[McpTool(
        name: 'volumes.delete',
        description: 'Deletes a Craft CMS asset volume.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    #[RequiresAdminChanges]
    public function delete(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?string $handle = null,
    ): array {
        $volume = $this->find($id, $uid, $handle);

        if (! $volume) {
            throw new ToolCallException('Volume not found.');
        }

        if (! $this->volumes->deleteVolume($volume)) {
            throw new ToolCallException('Volume could not be deleted.');
        }

        return ['deleted' => true];
    }

    private function find(?int $id, ?string $uid, ?string $handle): ?Volume
    {
        if (count(Arr::whereNotNull([$id, $uid, $handle])) !== 1) {
            throw new ToolCallException('Provide exactly one of: id, uid, handle.');
        }

        return match (true) {
            $id !== null => $this->volumes->getVolumeById($id),
            $uid !== null => $this->volumes->getVolumeByUid($uid),
            $handle !== null => $this->volumes->getVolumeByHandle($handle),
            default => null,
        };
    }

    /** @return array<string, mixed> */
    private function serializeSummary(Volume $volume): array
    {
        return [
            'id' => $volume->id,
            'uid' => $volume->uid,
            'name' => $volume->name,
            'handle' => $volume->handle,
            'fsHandle' => $volume->getFsHandle(false),
            'subpath' => $volume->getSubpath(ensureTrailing: false, parse: false),
        ];
    }

    /** @return array<string, mixed> */
    private function serialize(Volume $volume): array
    {
        return [
            ...$this->serializeSummary($volume),
            'assetTransformer' => $volume->getAssetTransformerHandle(false),
            'titleTranslationMethod' => $volume->titleTranslationMethod->value,
            'titleTranslationKeyFormat' => $volume->titleTranslationKeyFormat,
            'altTranslationMethod' => $volume->altTranslationMethod->value,
            'altTranslationKeyFormat' => $volume->altTranslationKeyFormat,
            'hasUrls' => $volume->hasUrls,
            'sortOrder' => $volume->sortOrder,
            'fieldLayout' => $this->fieldLayouts->serialize($volume->getFieldLayout()),
        ];
    }

    private function validationErrors(Volume $volume): string
    {
        return implode("\n", $volume->errors()->all()) ?: 'Volume could not be saved.';
    }

    private function saved(Volume $volume): Volume
    {
        $saved = $volume->id ? $this->volumes->getVolumeById($volume->id) : null;

        if (! $saved) {
            throw new ToolCallException('Saved volume could not be loaded.');
        }

        return $saved;
    }
}
