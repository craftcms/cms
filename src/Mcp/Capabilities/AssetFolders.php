<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Exceptions\AssetException;
use CraftCms\Cms\Asset\Folders;
use CraftCms\Cms\Filesystem\Exceptions\FilesystemException;
use CraftCms\Cms\Mcp\McpActor;
use CraftCms\Cms\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;

/**
 * @since 6.0.0
 */
readonly class AssetFolders
{
    private const array CriteriaSchema = [
        'type' => 'object',
        'properties' => [
            'id' => ['type' => 'integer', 'description' => 'Asset folder ID.'],
            'uid' => ['type' => 'string', 'format' => 'uuid', 'description' => 'Asset folder UID.'],
            'volumeId' => ['type' => 'integer', 'description' => 'Volume ID.'],
            'parentId' => ['type' => 'integer', 'description' => 'Parent folder ID.'],
            'name' => ['type' => 'string', 'description' => 'Folder name.'],
            'path' => ['type' => 'string', 'description' => 'Folder path.'],
            'offset' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Number of folders to skip.'],
            'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 500, 'description' => 'Maximum number of folders to return.'],
        ],
        'additionalProperties' => false,
    ];

    public function __construct(
        private McpActor $actor,
        private Folders $folders,
    ) {}

    /**
     * @param  array<string, mixed>  $criteria  Asset folder query criteria.
     * @return array{count: int, folders: list<array<string, mixed>>}
     */
    #[McpTool(
        name: 'asset-folders.list',
        description: 'Lists Craft CMS asset folders available to the current user.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function list(
        #[Schema(definition: self::CriteriaSchema)]
        array $criteria = [],
    ): array {
        $actor = $this->actor->user();
        $folders = $this->folders
            ->findFolders($criteria)
            ->filter(static fn (VolumeFolder $folder): bool => Gate::forUser($actor)->allows('viewContents', $folder))
            ->values();

        return [
            'count' => $folders->count(),
            'folders' => $folders->map($this->serialize(...))->all(),
        ];
    }

    /**
     * @param  int|null  $id  Asset folder ID.
     * @param  string|null  $uid  Asset folder UID.
     * @return array{folder: array<string, mixed>}
     */
    #[McpTool(
        name: 'asset-folders.get',
        description: 'Gets a Craft CMS asset folder by ID or UID.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function get(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
    ): array {
        $folder = $this->find($id, $uid);

        if (! $folder || ! Gate::forUser($this->actor->user())->allows('viewContents', $folder)) {
            throw new ToolCallException('Asset folder not found.');
        }

        return ['folder' => $this->serialize($folder)];
    }

    /** @return array{folder: array<string, mixed>} */
    #[McpTool(name: 'asset-folders.create', description: 'Creates a Craft CMS asset folder.')]
    public function create(
        #[Schema(minLength: 1, maxLength: 255)]
        string $name,
        int $parentId,
    ): array {
        $parent = $this->folders->getFolderById($parentId);

        if (! $parent) {
            throw new ToolCallException('Parent asset folder not found.');
        }

        if (! Gate::forUser($this->actor->user())->allows('createFolder', $parent)) {
            throw new ToolCallException('You are not authorized to create folders in this volume.');
        }

        $name = AssetsHelper::prepareAssetName($name, false);

        if ($name === '') {
            throw new ToolCallException('Provide an asset folder name.');
        }

        $folder = new VolumeFolder([
            'name' => $name,
            'parentId' => $parent->id,
            'volumeId' => $parent->volumeId,
            'path' => $parent->path.$name.'/',
        ]);

        try {
            $this->folders->createFolder($folder);
        } catch (AssetException|FilesystemException $exception) {
            throw new ToolCallException($exception->getMessage(), previous: $exception);
        }

        return ['folder' => $this->serialize($folder)];
    }

    /**
     * @param  int|null  $id  Asset folder ID.
     * @param  string|null  $uid  Asset folder UID.
     * @return array{folder: array<string, mixed>}
     */
    #[McpTool(
        name: 'asset-folders.update',
        description: 'Renames a Craft CMS asset folder.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    public function update(
        #[Schema(minLength: 1, maxLength: 255)]
        string $name,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
    ): array {
        $folder = $this->find($id, $uid);

        if (! $folder) {
            throw new ToolCallException('Asset folder not found.');
        }

        if (! Gate::forUser($this->actor->user())->allows('renameFolder', $folder)) {
            throw new ToolCallException('You are not authorized to rename this asset folder.');
        }

        try {
            $this->folders->renameFolderById((int) $folder->id, $name);
        } catch (AssetException|FilesystemException $exception) {
            throw new ToolCallException($exception->getMessage(), previous: $exception);
        }

        $folder = $this->folders->getFolderById((int) $folder->id);

        if (! $folder) {
            throw new ToolCallException('Asset folder not found after rename.');
        }

        return ['folder' => $this->serialize($folder)];
    }

    /**
     * @param  int|null  $id  Asset folder ID.
     * @param  string|null  $uid  Asset folder UID.
     * @return array{deleted: true}
     */
    #[McpTool(
        name: 'asset-folders.delete',
        description: 'Deletes a Craft CMS asset folder.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    public function delete(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        bool $deleteDirectory = true,
    ): array {
        $folder = $this->find($id, $uid);

        if (! $folder) {
            throw new ToolCallException('Asset folder not found.');
        }

        if (! Gate::forUser($this->actor->user())->allows('deleteFolder', $folder)) {
            throw new ToolCallException('You are not authorized to delete this asset folder.');
        }

        $this->folders->deleteFoldersByIds((int) $folder->id, $deleteDirectory);

        return ['deleted' => true];
    }

    private function find(?int $id, ?string $uid): ?VolumeFolder
    {
        if (count(Arr::whereNotNull([$id, $uid])) !== 1) {
            throw new ToolCallException('Provide exactly one of: id, uid.');
        }

        return match (true) {
            $id !== null => $this->folders->getFolderById($id),
            $uid !== null => $this->folders->getFolderByUid($uid),
            default => null,
        };
    }

    /** @return array<string, mixed> */
    private function serialize(VolumeFolder $folder): array
    {
        return [
            'id' => $folder->id,
            'uid' => $folder->uid,
            'name' => $folder->name,
            'path' => $folder->path,
            'parentId' => $folder->parentId,
            'volumeId' => $folder->volumeId,
            'volumeHandle' => $folder->volumeId ? $folder->getVolume()->handle : null,
        ];
    }
}
