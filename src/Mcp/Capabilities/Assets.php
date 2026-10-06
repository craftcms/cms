<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Asset\Assets as AssetService;
use CraftCms\Cms\Asset\AssetUploadHandler;
use CraftCms\Cms\Asset\Data\AssetIngest;
use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Enums\AssetIngestStatus;
use CraftCms\Cms\Asset\Folders;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\UserInitiatedElementSave;
use CraftCms\Cms\Filesystem\Data\UploadedFile;
use CraftCms\Cms\Filesystem\Data\UploadSessionData;
use CraftCms\Cms\Mcp\AssetUploads as McpAssetUploads;
use CraftCms\Cms\Mcp\ElementQueryCriteria;
use CraftCms\Cms\Mcp\ElementResourceLinks;
use CraftCms\Cms\Mcp\ElementSerializer;
use CraftCms\Cms\Mcp\McpActor;
use CraftCms\Cms\Mcp\Schema\CustomFieldSchema;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\User\Contracts\CraftUser;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ResourceReadException;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\ToolAnnotations;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * @since 6.0.0
 */
readonly class Assets
{
    private const array CriteriaSchema = [
        'type' => 'object',
        'properties' => [
            ...ElementQueryCriteria::SchemaProperties,
            ...ElementQueryCriteria::AssetSchemaProperties,
        ],
        'additionalProperties' => true,
    ];

    private const array CreateAttributesSchema = [
        'type' => 'object',
        'properties' => [
            'title' => ['type' => ['string', 'null'], 'description' => 'Asset title.'],
            'alt' => ['type' => ['string', 'null'], 'description' => 'Alternative text.'],
            'enabled' => ['type' => 'boolean', 'description' => 'Whether the asset is enabled.'],
            'siteId' => ['type' => 'integer', 'description' => 'Site ID for the asset element.'],
        ],
        'additionalProperties' => false,
    ];

    private const array UpdateAttributesSchema = [
        'type' => 'object',
        'properties' => [
            'title' => ['type' => ['string', 'null'], 'description' => 'Asset title.'],
            'slug' => ['type' => ['string', 'null'], 'description' => 'Asset slug.'],
            'alt' => ['type' => ['string', 'null'], 'description' => 'Alternative text.'],
            'enabled' => ['type' => 'boolean', 'description' => 'Whether the asset is enabled.'],
            'filename' => ['type' => 'string', 'description' => 'Asset filename.'],
            'folderId' => ['type' => 'integer', 'description' => 'Asset folder ID.'],
        ],
        'additionalProperties' => false,
    ];

    private const array FieldsSchema = [
        'type' => 'object',
        'description' => 'Custom field values keyed by field handle. Use assets.field-schema for the applicable schema.',
        'additionalProperties' => true,
    ];

    public function __construct(
        private McpActor $actor,
        private AssetService $assets,
        private AssetUploadHandler $assetUploads,
        private CustomFieldSchema $customFieldSchema,
        private ElementSerializer $elementSerializer,
        private Elements $elements,
        private ElementQueryCriteria $elementQueryCriteria,
        private ElementResourceLinks $resourceLinks,
        private Folders $folders,
        private McpAssetUploads $mcpAssetUploads,
        private Request $request,
        private UserInitiatedElementSave $userInitiatedElementSave,
    ) {}

    /**
     * @param  array<string, mixed>  $criteria  Native Craft AssetQuery criteria. Custom field criteria may be passed by field handle.
     */
    #[McpTool(
        name: 'assets.list',
        description: 'Lists Craft CMS assets.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function list(
        #[Schema(definition: self::CriteriaSchema)]
        array $criteria = [],
    ): CallToolResult {
        $actor = $this->actor->user();
        $query = Asset::find()->orderBy('elements.id');
        $criteria = $this->elementQueryCriteria->apply($query, $criteria);

        $assets = collect($query->all())
            ->filter(static fn (Asset $asset): bool => Gate::forUser($actor)->allows('view', $asset))
            ->values();

        return $this->resourceLinks->result([
            'count' => $assets->count(),
            'limit' => $criteria['limit'],
            'offset' => $criteria['offset'],
            'assets' => $assets->map(fn (Asset $asset): array => $this->elementSerializer->serialize($asset))->all(),
        ], $assets);
    }

    /**
     * @param  int|null  $id  Asset ID.
     * @param  string|null  $uid  Asset UID.
     * @param  int|null  $siteId  Site ID to load the asset in.
     * @return array{asset: array<string, mixed>}
     */
    #[McpTool(
        name: 'assets.get',
        description: 'Gets a Craft CMS asset by ID or UID.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function get(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
    ): array {
        $asset = $this->find($id, $uid, $siteId);

        if (! $asset || ! Gate::forUser($this->actor->user())->allows('view', $asset)) {
            throw new ToolCallException('Asset not found.');
        }

        return ['asset' => $this->elementSerializer->serialize($asset)];
    }

    /**
     * Returns the writable custom-field schema for an existing asset or an asset in the requested folder or volume.
     *
     * @return array{schema: array<string, mixed>}
     */
    #[McpTool(
        name: 'assets.field-schema',
        description: 'Gets the writable custom-field JSON Schema for an existing asset or an asset volume.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function fieldSchema(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
        ?int $folderId = null,
        ?int $volumeId = null,
    ): array {
        $existingAsset = $id !== null || $uid !== null;

        if (! $existingAsset && $folderId === null && $volumeId === null) {
            throw new ToolCallException('Provide an asset ID or UID, or provide folderId or volumeId for a new asset.');
        }

        if ($existingAsset && $volumeId !== null) {
            throw new ToolCallException('Use folderId to select a destination when requesting an existing asset schema.');
        }

        $actor = $this->actor->user();

        if ($existingAsset) {
            $asset = $this->find($id, $uid, $siteId);

            if (! $asset) {
                throw new ToolCallException('Asset not found.');
            }

            $this->authorizeSave($actor, $asset);

            if ($folderId !== null) {
                $folder = $this->folders->getFolderById($folderId);

                if (! $folder || ! Gate::forUser($actor)->allows('moveFile', [$asset, $folder])) {
                    throw new ToolCallException('You are not authorized to move this asset file.');
                }

                $asset->newFolderId = $folder->id;
                $asset->setVolumeId($folder->volumeId);
            }
        } else {
            $folder = $this->targetFolder($folderId, $volumeId);

            if (! $folder || ! Gate::forUser($actor)->allows('uploadAsset', $folder)) {
                throw new ToolCallException('You are not authorized to upload assets to this folder.');
            }

            $asset = new Asset;
            $asset->newFolderId = $folder->id;
            $asset->setVolumeId($folder->volumeId);
            $asset->uploaderId = $actor->getCraftUserId();
        }

        $this->authorizeSave($actor, $asset);

        return ['schema' => $this->customFieldSchema->forElement($asset)];
    }

    /** @return array{upload: array<string, mixed>} */
    #[McpTool(name: 'assets.upload.prepare', description: 'Starts a short-lived, resumable upload for assets.create.')]
    public function prepareUpload(
        #[Schema(minLength: 1, maxLength: 255)]
        string $filename,
        #[Schema(minimum: 1)]
        int $size,
        #[Schema(minimum: 1)]
        ?int $folderId = null,
        #[Schema(minimum: 1)]
        ?int $volumeId = null,
    ): array {
        $actor = $this->actor->user();
        $folder = $this->targetFolder($folderId, $volumeId);

        if (! $folder || ! Gate::forUser($actor)->allows('uploadAsset', $folder)) {
            throw new ToolCallException('You are not authorized to upload assets to this folder.');
        }

        try {
            $session = $this->mcpAssetUploads->start(
                $this->request,
                $filename,
                $size,
                [
                    'operation' => 'upload',
                    'folderId' => $folder->id,
                ],
            );
        } catch (AuthorizationException|HttpExceptionInterface $exception) {
            throw new ToolCallException(
                $exception->getMessage() ?: 'Upload could not be prepared.',
                previous: $exception,
            );
        }

        return ['upload' => $this->serializeUploadSession($session)];
    }

    /**
     * @param  array<string, mixed>  $attributes  Built-in asset attributes.
     * @param  array<string, mixed>  $fields  Custom field values keyed by field handle.
     * @return array{asset: array<string, mixed>}
     */
    #[McpTool(name: 'assets.create', description: 'Creates a Craft CMS asset from a completed assets.upload.prepare upload. Use assets.field-schema to discover custom fields.')]
    public function create(
        #[Schema(format: 'uuid')]
        string $uploadId,
        #[Schema(definition: self::CreateAttributesSchema)]
        array $attributes = [],
        #[Schema(definition: self::FieldsSchema)]
        array $fields = [],
    ): array {
        try {
            return $this->mcpAssetUploads->consume(
                $this->request,
                $uploadId,
                function (UploadedFile $source, array $parameters) use ($attributes, $fields): array {
                    $actor = $this->actor->user();
                    $folder = $this->folders->getFolderById((int) $parameters['folderId']);

                    if (! $folder) {
                        throw new ToolCallException('The upload destination no longer exists.');
                    }

                    $asset = new Asset;
                    Typecast::configure($asset, $attributes);
                    $asset->newFolderId = $folder->id;
                    $asset->setVolumeId($folder->volumeId);
                    $asset->uploaderId = $actor->getCraftUserId();
                    $asset->setFieldValues($fields);
                    $this->authorizeSave($actor, $asset);

                    $result = $this->assetUploads->ingest(new AssetIngest(
                        source: $source,
                        filename: $source->filename,
                        mimeType: $source->mimeType(),
                        folder: $folder,
                        sanitizeOnUpload: true,
                        uploaderId: $actor->getCraftUserId(),
                        asset: $asset,
                    ));

                    if ($result->status !== AssetIngestStatus::Saved) {
                        throw new ToolCallException(
                            $result->message
                            ?? implode("\n", $result->asset->errors()->all())
                            ?: 'Asset could not be created.',
                        );
                    }

                    return ['asset' => $this->elementSerializer->serialize($result->asset)];
                },
            );
        } catch (ModelNotFoundException) {
            throw new ToolCallException('Upload not found or no longer available.');
        } catch (AuthorizationException|HttpExceptionInterface $exception) {
            throw new ToolCallException(
                $exception->getMessage() ?: 'Upload not found or no longer available.',
                previous: $exception,
            );
        }
    }

    /**
     * @param  int|null  $id  Asset ID.
     * @param  string|null  $uid  Asset UID.
     * @param  int|null  $siteId  Site ID to load the asset in.
     * @param  array<string, mixed>  $attributes  Built-in asset attributes to update.
     * @param  array<string, mixed>  $fields  Custom field values keyed by field handle.
     * @return array{asset: array<string, mixed>}
     */
    #[McpTool(
        name: 'assets.update',
        description: 'Updates a Craft CMS asset. Use assets.field-schema to discover custom fields.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    public function update(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
        #[Schema(definition: self::UpdateAttributesSchema)]
        array $attributes = [],
        #[Schema(definition: self::FieldsSchema)]
        array $fields = [],
    ): array {
        $asset = $this->find($id, $uid, $siteId);

        if (! $asset) {
            throw new ToolCallException('Asset not found.');
        }

        $actor = $this->actor->user();
        $this->authorizeSave($actor, $asset);
        $relocation = $this->relocation($asset, $attributes, $actor);

        Typecast::configure($asset, Arr::except($attributes, ['filename', 'folderId']));
        $asset->setFieldValues($fields);
        $this->authorizeSave($actor, $asset);

        if ($relocation !== null) {
            if (! $this->assets->moveAsset($asset, $relocation['folder'], $relocation['filename'])) {
                throw new ToolCallException($this->validationErrors($asset));
            }

            return ['asset' => $this->elementSerializer->serialize($asset)];
        }

        return ['asset' => $this->save($asset, $actor)];
    }

    /**
     * @param  int|null  $id  Asset ID.
     * @param  string|null  $uid  Asset UID.
     * @param  int|null  $siteId  Site ID to load the asset in.
     * @return array{deleted: true}
     */
    #[McpTool(
        name: 'assets.delete',
        description: 'Deletes a Craft CMS asset.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    public function delete(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
        bool $hardDelete = false,
    ): array {
        $asset = $this->find($id, $uid, $siteId);

        if (! $asset || ! Gate::forUser($this->actor->user())->allows('delete', $asset)) {
            throw new ToolCallException('Asset not found.');
        }

        if (! $this->elements->deleteElement($asset, $hardDelete)) {
            throw new ToolCallException('Asset could not be deleted.');
        }

        return ['deleted' => true];
    }

    /** @return array{asset: array<string, mixed>} */
    #[McpResourceTemplate(
        uriTemplate: ElementResourceLinks::Templates[Asset::class],
        name: 'craft-assets-get',
        title: 'Craft Asset',
        description: 'A JSON Craft CMS asset record addressed by element ID and site ID.',
        mimeType: 'application/json',
    )]
    public function resourceByIdAndSite(int $id, int $siteId): array
    {
        $asset = $this->elements->getElementById($id, Asset::class, $siteId, [
            'status' => [Asset::STATUS_ENABLED, Asset::STATUS_DISABLED, Asset::STATUS_ARCHIVED],
            'trashed' => null,
        ]);

        if (! $asset || ! Gate::forUser($this->actor->user())->allows('view', $asset)) {
            throw new ResourceReadException('Asset not found.');
        }

        return ['asset' => $this->elementSerializer->serialize($asset)];
    }

    private function find(?int $id = null, ?string $uid = null, ?int $siteId = null): ?Asset
    {
        if (count(Arr::whereNotNull([$id, $uid])) !== 1) {
            throw new ToolCallException('Provide exactly one of: id, uid.');
        }

        $query = Asset::find();
        Typecast::configure($query, Arr::whereNotNull([
            'id' => $id,
            'uid' => $uid,
            'siteId' => $siteId,
        ]));

        return $query->one();
    }

    private function targetFolder(?int $folderId, ?int $volumeId): ?VolumeFolder
    {
        if (($folderId === null) === ($volumeId === null)) {
            throw new ToolCallException('Provide exactly one of: folderId, volumeId.');
        }

        return $folderId !== null
            ? $this->folders->getFolderById($folderId)
            : $this->folders->getRootFolderByVolumeId($volumeId);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{folder: VolumeFolder, filename: string}|null
     */
    private function relocation(Asset $asset, array $attributes, CraftUser $actor): ?array
    {
        if (! array_key_exists('filename', $attributes) && ! array_key_exists('folderId', $attributes)) {
            return null;
        }

        $filename = $attributes['filename'] ?? $asset->getFilename();
        $folderId = $attributes['folderId'] ?? $asset->folderId;
        $folder = is_int($folderId) ? $this->folders->getFolderById($folderId) : null;

        if (! is_string($filename) || $filename === '' || ! $folder) {
            throw new ToolCallException('Asset relocation requires a valid filename and folderId.');
        }

        if ($filename === $asset->getFilename() && $folder->id === $asset->folderId) {
            return null;
        }

        if (! Gate::forUser($actor)->allows('moveFile', [$asset, $folder])) {
            throw new ToolCallException('You are not authorized to move this asset file.');
        }

        return ['folder' => $folder, 'filename' => $filename];
    }

    private function authorizeSave(CraftUser $actor, Asset $asset): void
    {
        if (! Gate::forUser($actor)->allows('save', $asset)) {
            throw new ToolCallException('You are not authorized to save this asset.');
        }
    }

    /** @return array<string, mixed> */
    private function save(Asset $asset, CraftUser $actor): array
    {
        $result = $this->userInitiatedElementSave->save($asset, $actor);

        if (! $result->successful || ! $result->element instanceof Asset) {
            throw new ToolCallException($this->validationErrors($result->element));
        }

        return $this->elementSerializer->serialize($result->element);
    }

    private function validationErrors(object $model): string
    {
        return method_exists($model, 'errors')
            ? implode("\n", $model->errors()->all()) ?: 'Asset could not be saved.'
            : 'Asset could not be saved.';
    }

    /** @return array<string, mixed> */
    private function serializeUploadSession(UploadSessionData $session): array
    {
        $transferUrl = route('craft.cp.mcp.uploads.transfer', ['upload' => $session->id]);
        $transport = $session->transport;

        if ($transport['type'] === 'tus') {
            $transport['options']['url'] = $transferUrl;
        }

        return [
            'id' => $session->id,
            'chunkSize' => $session->chunkSize,
            'partCount' => $session->partCount,
            'expiresInSeconds' => Cms::config()->uploadSessionDuration,
            'transport' => $transport,
            'urls' => [
                'transfer' => $transferUrl,
                'status' => route('craft.cp.mcp.uploads.status', ['upload' => $session->id]),
                'cancel' => route('craft.cp.mcp.uploads.destroy', ['upload' => $session->id]),
            ],
            'authorization' => [
                'type' => 'bearer',
                'source' => 'mcp',
            ],
        ];
    }
}
