<?php

declare(strict_types=1);

namespace craft\mcp\elements\adapters;

use Craft;
use craft\base\ElementInterface;
use craft\elements\Asset;
use craft\elements\User;
use craft\mcp\Authorization;
use craft\mcp\ElementLifecycle;
use craft\mcp\ElementQueryCriteria;
use craft\mcp\elements\BaseElementAdapter;
use craft\mcp\serializers\ElementSerializer;
use craft\models\VolumeFolder;
use craft\services\Assets;
use craft\services\Assets as Folders;
use Illuminate\Support\Arr;
use Mcp\Exception\ToolCallException;

/**
 * @since 5.12.0
 */
class AssetAdapter extends BaseElementAdapter
{
    public function __construct(
        ElementSerializer $elementSerializer,
        ElementLifecycle $lifecycle,
        private readonly Assets $assets,
        private readonly Folders $folders,
    ) {
        parent::__construct($elementSerializer, $lifecycle);
    }

    public static function handle(): string
    {
        return 'assets';
    }

    public static function elementType(): string
    {
        return Asset::class;
    }

    protected function criteriaProperties(): array
    {
        return ElementQueryCriteria::AssetSchemaProperties;
    }

    protected function updateAttributesSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => ['string', 'null'], 'description' => 'Asset title.'],
                'slug' => ['type' => ['string', 'null'], 'description' => 'Asset slug.'],
                'alt' => ['type' => ['string', 'null'], 'description' => 'Alternative text.'],
                'enabled' => ['type' => 'boolean', 'description' => 'Whether the asset is enabled.'],
                'filename' => ['type' => 'string', 'description' => 'Asset filename. Changing it renames the file.'],
                'folderId' => ['type' => 'integer', 'description' => 'Asset folder ID. Changing it moves the file.'],
                'focalPoint' => [
                    'type' => ['object', 'null'],
                    'description' => 'Image or video focal point, as fractions of the width (x) and height (y) from the top-left corner. Image transforms crop around it. Null clears it.',
                    'properties' => [
                        'x' => ['type' => 'number', 'minimum' => 0, 'maximum' => 1],
                        'y' => ['type' => 'number', 'minimum' => 0, 'maximum' => 1],
                    ],
                    'required' => ['x', 'y'],
                    'additionalProperties' => false,
                ],
            ],
            'additionalProperties' => false,
        ];
    }

    protected function fieldSchemaContextSchema(): array
    {
        return [
            'type' => 'object',
            'description' => 'For a new asset, provide folderId or volumeId. For an existing asset, folderId previews the layout after a move.',
            'properties' => [
                'folderId' => ['type' => 'integer', 'description' => 'Destination folder ID.'],
                'volumeId' => ['type' => 'integer', 'description' => 'Destination volume ID, using its root folder.'],
            ],
            'additionalProperties' => false,
        ];
    }

    protected function notes(): array
    {
        return [
            'Create assets with assets.create and replace their files with assets.replace; both accept the file to upload.',
            'Restoring an asset only succeeds if its file was kept when it was deleted.',
            'Validation does not move or rename files.',
        ];
    }

    public function create(array $attributes, array $fields, User $actor): array
    {
        throw new ToolCallException('Create assets with assets.create, which accepts the file to upload.');
    }

    /** @param Asset|null $element */
    public function fieldSchemaElement(?ElementInterface $element, array $context, User $actor): ElementInterface
    {
        $folderId = $context['folderId'] ?? null;
        $volumeId = $context['volumeId'] ?? null;

        if ($element === null && $folderId === null && $volumeId === null) {
            throw new ToolCallException('Provide an asset ID or UID, or provide context.folderId or context.volumeId for a new asset.');
        }

        if ($element === null) {
            $folder = $this->targetFolder($folderId, $volumeId);

            if (!$folder || !Authorization::allows($actor, 'uploadAsset', $folder)) {
                throw new ToolCallException('You are not authorized to upload assets to this folder.');
            }

            $element = new Asset();
            $element->newFolderId = $folder->id;
            $element->setVolumeId($folder->volumeId);
            $element->uploaderId = $actor->id;

            return parent::fieldSchemaElement($element, $context, $actor);
        }

        if ($volumeId !== null) {
            throw new ToolCallException('Use context.folderId to select a destination when requesting an existing asset schema.');
        }

        $this->authorizeSave($actor, $element);

        if ($folderId !== null) {
            $folder = $this->folders->getFolderById($folderId);

            if (!$folder || !Authorization::allows($actor, 'moveFile', [$element, $folder])) {
                throw new ToolCallException('You are not authorized to move this asset file.');
            }

            $element->newFolderId = $folder->id;
            $element->setVolumeId($folder->volumeId);
        }

        return parent::fieldSchemaElement($element, $context, $actor);
    }

    /** @param Asset $element */
    public function update(ElementInterface $element, array $attributes, array $fields, User $actor): array
    {
        $this->authorizeSave($actor, $element);
        $relocation = $this->relocation($element, $attributes, $actor);

        Craft::configure($element, Arr::except($attributes, ['filename', 'folderId']));
        $element->setFieldValues($fields);
        $this->authorizeSave($actor, $element);

        if ($relocation === null) {
            return $this->save($element, $actor);
        }

        if (!$this->assets->moveAsset($element, $relocation['folder'], $relocation['filename'])) {
            throw new ToolCallException(implode("\n", $element->getErrorSummary(true)) ?: 'Asset could not be saved.');
        }

        return ['element' => $this->serialize($element)];
    }

    /** @param Asset $element */
    public function prepareValidation(ElementInterface $element, array $attributes, array $fields, User $actor): void
    {
        $this->authorizeSave($actor, $element);
        $this->relocation($element, $attributes, $actor);
        Craft::configure($element, $attributes);
        $element->setFieldValues($fields);
        $this->authorizeSave($actor, $element);
    }

    /**
     * Resolves an upload destination from exactly one of a folder ID or a volume ID.
     */
    public function targetFolder(?int $folderId, ?int $volumeId): ?VolumeFolder
    {
        if (($folderId === null) === ($volumeId === null)) {
            throw new ToolCallException('Provide exactly one of: folderId, volumeId.');
        }

        return $folderId !== null
            ? $this->folders->getFolderById($folderId)
            : $this->folders->getRootFolderByVolumeId($volumeId);
    }

    /**
     * Checks that the actor may save the asset.
     */
    public function authorizeAssetSave(User $actor, Asset $asset): void
    {
        $this->authorizeSave($actor, $asset);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{folder: VolumeFolder, filename: string}|null
     */
    private function relocation(Asset $asset, array $attributes, User $actor): ?array
    {
        if (!array_key_exists('filename', $attributes) && !array_key_exists('folderId', $attributes)) {
            return null;
        }

        $filename = $attributes['filename'] ?? $asset->getFilename();
        $folderId = $attributes['folderId'] ?? $asset->folderId;
        $folder = is_int($folderId) ? $this->folders->getFolderById($folderId) : null;

        if (!is_string($filename) || $filename === '' || !$folder) {
            throw new ToolCallException('Asset relocation requires a valid filename and folderId.');
        }

        if ($filename === $asset->getFilename() && $folder->id === $asset->folderId) {
            return null;
        }

        if (!Authorization::allows($actor, 'moveFile', [$asset, $folder])) {
            throw new ToolCallException('You are not authorized to move this asset file.');
        }

        return ['folder' => $folder, 'filename' => $filename];
    }
}
