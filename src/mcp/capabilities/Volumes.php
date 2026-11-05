<?php

declare(strict_types=1);

namespace craft\mcp\capabilities;

use Craft;
use craft\elements\Asset;
use craft\mcp\attributes\RequiresAdmin;
use craft\mcp\attributes\RequiresAdminChanges;
use craft\mcp\schema\FieldLayoutConfig;
use craft\models\Volume;
use craft\services\Volumes as VolumeService;
use Illuminate\Support\Arr;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Server\RequestContext;

/**
 * @since 5.12.0
 */
class Volumes
{
    public function __construct(
        private FieldLayoutConfig $fieldLayouts,
        private VolumeService $volumes,
    ) {
    }

    /** @return array{count: int, volumes: list<array<string, mixed>>} */
    #[RequiresAdmin]
    public function list(): array
    {
        $volumes = collect($this->volumes->getAllVolumes())
            ->map($this->serializeSummary(...))
            ->values();

        return [
            'count' => $volumes->count(),
            'volumes' => $volumes->all(),
        ];
    }

    /** @return array{volume: array<string, mixed>} */
    #[RequiresAdmin]
    public function get(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?string $handle = null,
    ): array {
        $volume = $this->find($id, $uid, $handle);

        if (!$volume) {
            throw new ToolCallException('Volume not found.');
        }

        return ['volume' => $this->serialize($volume)];
    }

    /**
     * @param  string  $name  Volume name.
     * @param  string  $fsHandle  Registered Craft filesystem handle.
     * @param  string|null  $subpath  Asset subpath within the disk.
     * @param  string  $titleTranslationMethod  Asset title translation method.
     * @param  string|null  $titleTranslationKeyFormat  Custom asset title translation key format.
     * @param  string  $altTranslationMethod  Asset alternative-text translation method.
     * @param  string|null  $altTranslationKeyFormat  Custom asset alternative-text translation key format.
     * @param  array<string, mixed>|null  $fieldLayout  Native Craft field layout config.
     * @return array{volume: array<string, mixed>}
     */
    #[RequiresAdminChanges]
    public function create(
        string $name,
        string $handle,
        string $fsHandle,
        ?string $subpath = null,
        #[Schema(enum: ['none', 'site', 'siteGroup', 'language', 'custom'])]
        string $titleTranslationMethod = 'site',
        ?string $titleTranslationKeyFormat = null,
        #[Schema(enum: ['none', 'site', 'siteGroup', 'language', 'custom'])]
        string $altTranslationMethod = 'none',
        ?string $altTranslationKeyFormat = null,
        #[Schema(definition: FieldLayoutConfig::Schema)]
        ?array $fieldLayout = null,
    ): array {
        $volume = new Volume([
            'name' => $name,
            'handle' => $handle,
            'fsHandle' => $fsHandle,
            'subpath' => $subpath,
            'titleTranslationMethod' => $titleTranslationMethod,
            'titleTranslationKeyFormat' => $titleTranslationKeyFormat,
            'altTranslationMethod' => $altTranslationMethod,
            'altTranslationKeyFormat' => $altTranslationKeyFormat,
        ]);

        if ($fieldLayout !== null) {
            $volume->setFieldLayout($this->fieldLayouts->make($fieldLayout, Asset::class));
        }

        if (!$this->volumes->saveVolume($volume)) {
            throw new ToolCallException($this->validationErrors($volume));
        }

        return ['volume' => $this->serialize($this->saved($volume))];
    }

    /**
     * @param  string|null  $currentHandle  Existing volume handle.
     * @param  string|null  $name  Volume name.
     * @param  string|null  $handle  New volume handle.
     * @param  string|null  $fsHandle  Registered Craft filesystem handle.
     * @param  string|null  $subpath  Asset subpath within the disk.
     * @param  string  $titleTranslationMethod  Asset title translation method.
     * @param  string|null  $titleTranslationKeyFormat  Custom asset title translation key format.
     * @param  string  $altTranslationMethod  Asset alternative-text translation method.
     * @param  string|null  $altTranslationKeyFormat  Custom asset alternative-text translation key format.
     * @param  array<string, mixed>|null  $fieldLayout  Native Craft field layout config.
     * @return array{volume: array<string, mixed>}
     */
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
        #[Schema(enum: ['none', 'site', 'siteGroup', 'language', 'custom'])]
        string $titleTranslationMethod = 'site',
        ?string $titleTranslationKeyFormat = null,
        #[Schema(enum: ['none', 'site', 'siteGroup', 'language', 'custom'])]
        string $altTranslationMethod = 'none',
        ?string $altTranslationKeyFormat = null,
        #[Schema(definition: FieldLayoutConfig::NullableSchema)]
        ?array $fieldLayout = null,
    ): array {
        $volume = $this->find($id, $uid, $currentHandle);

        if (!$volume) {
            throw new ToolCallException('Volume not found.');
        }

        $request = $context->getRequest();
        assert($request instanceof CallToolRequest);

        Craft::configure($volume, array_intersect_key([
            'name' => $name,
            'handle' => $handle,
            'fsHandle' => $fsHandle,
            'subpath' => $subpath,
            'titleTranslationMethod' => $titleTranslationMethod,
            'titleTranslationKeyFormat' => $titleTranslationKeyFormat,
            'altTranslationMethod' => $altTranslationMethod,
            'altTranslationKeyFormat' => $altTranslationKeyFormat,
        ], $request->arguments));

        if (array_key_exists('fieldLayout', $request->arguments)) {
            $volume->setFieldLayout($this->fieldLayouts->make(
                $fieldLayout ?? [],
                Asset::class,
                $volume->getFieldLayout(),
            ));
        }

        if (!$this->volumes->saveVolume($volume)) {
            throw new ToolCallException($this->validationErrors($volume));
        }

        return ['volume' => $this->serialize($this->saved($volume))];
    }

    /** @return array{deleted: true} */
    #[RequiresAdminChanges]
    public function delete(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?string $handle = null,
    ): array {
        $volume = $this->find($id, $uid, $handle);

        if (!$volume) {
            throw new ToolCallException('Volume not found.');
        }

        if (!$this->volumes->deleteVolume($volume)) {
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
            'titleTranslationMethod' => $volume->titleTranslationMethod,
            'titleTranslationKeyFormat' => $volume->titleTranslationKeyFormat,
            'altTranslationMethod' => $volume->altTranslationMethod,
            'altTranslationKeyFormat' => $volume->altTranslationKeyFormat,
            'hasUrls' => $volume->getFs()->hasUrls,
            'sortOrder' => $volume->sortOrder,
            'fieldLayout' => $this->fieldLayouts->serialize($volume->getFieldLayout()),
        ];
    }

    private function validationErrors(Volume $volume): string
    {
        return implode("\n", $volume->getErrorSummary(true)) ?: 'Volume could not be saved.';
    }

    private function saved(Volume $volume): Volume
    {
        $saved = $volume->id ? $this->volumes->getVolumeById($volume->id) : null;

        if (!$saved) {
            throw new ToolCallException('Saved volume could not be loaded.');
        }

        return $saved;
    }
}
