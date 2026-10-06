<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Asset\Assets as AssetService;
use CraftCms\Cms\Asset\AssetUploadHandler;
use CraftCms\Cms\Asset\Data\AssetIngest;
use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Enums\AssetIngestStatus;
use CraftCms\Cms\Asset\Exceptions\AssetTransformException;
use CraftCms\Cms\Asset\Folders;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\UserInitiatedElementSave;
use CraftCms\Cms\Filesystem\Data\UploadedFile;
use CraftCms\Cms\Filesystem\Data\UploadSessionData;
use CraftCms\Cms\Filesystem\Exceptions\FilesystemException;
use CraftCms\Cms\Mcp\AssetUploads as McpAssetUploads;
use CraftCms\Cms\Mcp\ElementLifecycle;
use CraftCms\Cms\Mcp\ElementQueryCriteria;
use CraftCms\Cms\Mcp\ElementResourceLinks;
use CraftCms\Cms\Mcp\McpActor;
use CraftCms\Cms\Mcp\Schema\CustomFieldSchema;
use CraftCms\Cms\Mcp\Serializers\ElementSerializer;
use CraftCms\Cms\Shared\Exceptions\NotSupportedException;
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
use RuntimeException;
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

    private const array FileSchema = [
        'type' => 'object',
        'description' => 'A downloadable file reference supplied by the client. URLs must be direct, temporary HTTPS URLs. Local paths and base64 are not supported.',
        'properties' => [
            'download_url' => ['type' => 'string', 'format' => 'uri'],
            'file_id' => ['type' => 'string', 'minLength' => 1],
            'mime_type' => ['type' => 'string'],
            'file_name' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 255],
        ],
        'required' => ['download_url', 'file_id'],
        'additionalProperties' => false,
    ];

    public function __construct(
        private McpActor $actor,
        private AssetService $assets,
        private AssetUploadHandler $assetUploads,
        private CustomFieldSchema $customFieldSchema,
        private ElementSerializer $elementSerializer,
        private Elements $elements,
        private ElementLifecycle $lifecycle,
        private ElementQueryCriteria $elementQueryCriteria,
        private ElementResourceLinks $resourceLinks,
        private Folders $folders,
        private McpAssetUploads $mcpAssetUploads,
        private Request $request,
        private UserInitiatedElementSave $userInitiatedElementSave,
    ) {}

    /**
     * @param  array<string, mixed>  $criteria  Native Craft AssetQuery criteria. Custom field criteria may be passed by field handle.
     * @param  list<string>|null  $fields
     */
    #[McpTool(
        name: 'assets.list',
        description: 'Lists Craft CMS assets.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function list(
        #[Schema(definition: self::CriteriaSchema)]
        array $criteria = [],
        #[Schema(definition: ElementSerializer::FieldsSchema)]
        ?array $fields = [],
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
            'assets' => $assets->map(fn (Asset $asset): array => $this->elementSerializer->serialize($asset, fields: $fields))->all(),
        ], $assets);
    }

    /**
     * @param  int|null  $id  Asset ID.
     * @param  string|null  $uid  Asset UID.
     * @param  int|null  $siteId  Site ID to load the asset in.
     * @param  list<string>|null  $fields
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
        #[Schema(definition: ElementSerializer::FieldsSchema)]
        ?array $fields = null,
    ): array {
        $asset = $this->find($id, $uid, $siteId);

        if (! $asset || ! Gate::forUser($this->actor->user())->allows('view', $asset)) {
            throw new ToolCallException('Asset not found.');
        }

        return ['asset' => $this->elementSerializer->serialize($asset, fields: $fields)];
    }

    /**
     * @param  array<string, mixed>|string  $transform  Named image transform handle or inline transform parameters.
     * @param  int|null  $id  Asset ID.
     * @param  string|null  $uid  Asset UID.
     * @param  int|null  $siteId  Site ID to load the asset in.
     * @param  string|null  $transformer  Existing asset transformer handle. Defaults to the volume's transformer, then the configured default.
     * @return array{url: string, mimeType: string, width: int|null, height: int|null}
     */
    #[McpTool(
        name: 'assets.transform-url',
        description: 'Gets a transformed asset URL for previewing. Accepts a named image transform handle or inline parameters such as width, height, mode, and format. Honors the selected transformer\'s generation settings, so this may queue or generate a transform and return a temporary generation URL.',
        annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false),
    )]
    public function transformUrl(
        #[Schema(definition: [
            'anyOf' => [
                ['type' => 'string', 'minLength' => 1],
                ['type' => 'object', 'additionalProperties' => true],
            ],
        ])]
        array|string $transform,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
        ?string $transformer = null,
    ): array {
        $asset = $this->find($id, $uid, $siteId);

        if (! $asset || ! Gate::forUser($this->actor->user())->allows('view', $asset)) {
            throw new ToolCallException('Asset not found.');
        }

        try {
            $result = $asset->transform($transform, $transformer);
        } catch (AssetTransformException|NotSupportedException|FilesystemException $exception) {
            throw new ToolCallException($exception->getMessage(), previous: $exception);
        }

        return [
            'url' => $result->url,
            'mimeType' => $result->mimeType,
            'width' => $result->width,
            'height' => $result->height,
        ];
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
    #[McpTool(name: 'assets.upload.prepare', description: 'Starts a short-lived, resumable upload. Provide folderId or volumeId for assets.create, or assetId for assets.replace.')]
    public function prepareUpload(
        #[Schema(minLength: 1, maxLength: 255)]
        string $filename,
        #[Schema(minimum: 1)]
        int $size,
        #[Schema(minimum: 1)]
        ?int $folderId = null,
        #[Schema(minimum: 1)]
        ?int $volumeId = null,
        #[Schema(minimum: 1)]
        ?int $assetId = null,
    ): array {
        $actor = $this->actor->user();

        if ($assetId !== null) {
            if ($folderId !== null || $volumeId !== null) {
                throw new ToolCallException('Provide either assetId or an upload destination, not both.');
            }

            $parameters = ['operation' => 'replace', 'assetId' => $assetId];
        } else {
            $folder = $this->targetFolder($folderId, $volumeId);

            if (! $folder || ! Gate::forUser($actor)->allows('uploadAsset', $folder)) {
                throw new ToolCallException('You are not authorized to upload assets to this folder.');
            }

            $parameters = ['operation' => 'upload', 'folderId' => $folder->id];
        }

        try {
            $session = $this->mcpAssetUploads->start(
                $this->request,
                $filename,
                $size,
                $parameters,
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
     * @param  array{download_url: string, file_id: string, mime_type?: string, file_name?: string}|null  $file  File reference. Provide folderId or volumeId when using this input.
     * @param  array<string, mixed>  $attributes  Built-in asset attributes.
     * @param  array<string, mixed>  $fields  Custom field values keyed by field handle.
     * @param  string|null  $filename  Filename override for a file reference. Required if file_name is absent.
     * @return array{asset: array<string, mixed>}
     */
    #[McpTool(
        name: 'assets.create',
        description: 'Creates an asset from exactly one source: a client-provided file reference plus folderId or volumeId, or a completed assets.upload.prepare uploadId. Use file references when the client cannot perform binary transfers. Use assets.field-schema to discover custom fields.',
        meta: ['openai/fileParams' => ['file']],
    )]
    public function create(
        #[Schema(format: 'uuid')]
        ?string $uploadId = null,
        #[Schema(definition: self::CreateAttributesSchema)]
        array $attributes = [],
        #[Schema(definition: self::FieldsSchema)]
        array $fields = [],
        #[Schema(definition: self::FileSchema)]
        ?array $file = null,
        #[Schema(minimum: 1)]
        ?int $folderId = null,
        #[Schema(minimum: 1)]
        ?int $volumeId = null,
        #[Schema(minLength: 1, maxLength: 255)]
        ?string $filename = null,
    ): array {
        if (($uploadId === null) === ($file === null)) {
            throw new ToolCallException('Provide exactly one of: uploadId, file.');
        }

        if ($uploadId !== null && ($folderId !== null || $volumeId !== null || $filename !== null)) {
            throw new ToolCallException('The uploadId already specifies the filename and destination.');
        }

        try {
            if ($file !== null) {
                $folder = $this->targetFolder($folderId, $volumeId);

                if (! $folder) {
                    throw new ToolCallException('Provide a valid folderId or volumeId for the file reference.');
                }

                $filename ??= $file['file_name'] ?? null;

                if ($filename === null || trim($filename) === '') {
                    throw new ToolCallException('Provide filename when the file reference does not include file_name.');
                }

                $this->mcpAssetUploads->authorize($this->request, ['folderId' => $folder->id], $filename, 0);
                $asset = $this->newAsset($folder, $attributes, $fields);

                return $this->mcpAssetUploads->consumeFile(
                    $file['download_url'],
                    $filename,
                    fn (UploadedFile $source): array => $this->ingestAsset($source, $folder, $asset),
                );
            }

            return $this->mcpAssetUploads->consume(
                $this->request,
                $uploadId,
                function (UploadedFile $source, array $parameters) use ($attributes, $fields): array {
                    $folder = $this->folders->getFolderById((int) $parameters['folderId']);

                    if (! $folder) {
                        throw new ToolCallException('The upload destination no longer exists.');
                    }

                    return $this->ingestAsset($source, $folder, $this->newAsset($folder, $attributes, $fields));
                },
            );
        } catch (ModelNotFoundException) {
            throw new ToolCallException('Upload not found or no longer available.');
        } catch (AuthorizationException|HttpExceptionInterface $exception) {
            throw new ToolCallException(
                $exception->getMessage() ?: 'Upload not found or no longer available.',
                previous: $exception,
            );
        } catch (RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        }
    }

    /**
     * @param  array{download_url: string, file_id: string, mime_type?: string, file_name?: string}|null  $file  File reference.
     * @param  string|null  $filename  Filename override for a file reference. Required if file_name is absent.
     * @return array{asset: array<string, mixed>}
     */
    #[McpTool(
        name: 'assets.replace',
        description: 'Replaces an existing asset file while preserving its ID and references, using exactly one source: a client-provided file reference or a completed assets.upload.prepare uploadId bound to assetId. Uses the HTTP replacement behavior, including adopting the incoming filename, which may change the URL. Uploads are consumed once; inspect the asset after an ambiguous failure before retrying.',
        annotations: new ToolAnnotations(destructiveHint: true),
        meta: ['openai/fileParams' => ['file']],
    )]
    public function replace(
        #[Schema(minimum: 1)]
        int $assetId,
        #[Schema(format: 'uuid')]
        ?string $uploadId = null,
        #[Schema(definition: self::FileSchema)]
        ?array $file = null,
        #[Schema(minLength: 1, maxLength: 255)]
        ?string $filename = null,
    ): array {
        $this->actor->user();

        if (($uploadId === null) === ($file === null)) {
            throw new ToolCallException('Provide exactly one of: uploadId, file.');
        }

        if ($uploadId !== null && $filename !== null) {
            throw new ToolCallException('The uploadId already specifies the filename.');
        }

        try {
            if ($file !== null) {
                $filename ??= $file['file_name'] ?? null;

                if ($filename === null || trim($filename) === '') {
                    throw new ToolCallException('Provide filename when the file reference does not include file_name.');
                }

                $this->mcpAssetUploads->authorize($this->request, ['operation' => 'replace', 'assetId' => $assetId], $filename, 0);

                return $this->mcpAssetUploads->consumeFile(
                    $file['download_url'],
                    $filename,
                    fn (UploadedFile $source): array => $this->replaceAsset($assetId, $source),
                );
            }

            return $this->mcpAssetUploads->consume(
                $this->request,
                $uploadId,
                fn (UploadedFile $source): array => $this->replaceAsset($assetId, $source),
                operation: 'replace',
                assetId: $assetId,
            );
        } catch (ModelNotFoundException) {
            throw new ToolCallException('Upload not found or no longer available.');
        } catch (AuthorizationException|HttpExceptionInterface $exception) {
            throw new ToolCallException(
                $exception->getMessage() ?: 'Asset file could not be replaced.',
                previous: $exception,
            );
        } catch (RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        }
    }

    /** @return array{asset: array<string, mixed>} */
    private function replaceAsset(int $assetId, UploadedFile $source): array
    {
        $result = $this->assetUploads->replace($assetId, $source);

        if ($result->status >= 400) {
            throw new ToolCallException(implode("\n", Arr::flatten($result->errors ?? [])) ?: 'Asset file could not be replaced.');
        }

        $asset = $this->assets->getAssetById($assetId);

        if (! $asset) {
            throw new ToolCallException('Asset not found.');
        }

        return ['asset' => $this->elementSerializer->serialize($asset)];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $fields
     */
    private function newAsset(VolumeFolder $folder, array $attributes, array $fields): Asset
    {
        $actor = $this->actor->user();
        $asset = new Asset;
        Typecast::configure($asset, $attributes);
        $asset->newFolderId = $folder->id;
        $asset->setVolumeId($folder->volumeId);
        $asset->uploaderId = $actor->getCraftUserId();
        $asset->setFieldValues($fields);
        $this->authorizeSave($actor, $asset);

        return $asset;
    }

    /** @return array{asset: array<string, mixed>} */
    private function ingestAsset(UploadedFile $source, VolumeFolder $folder, Asset $asset): array
    {
        $result = $this->assetUploads->ingest(new AssetIngest(
            source: $source,
            filename: $source->filename,
            mimeType: $source->mimeType(),
            folder: $folder,
            sanitizeOnUpload: true,
            uploaderId: $asset->uploaderId,
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

    /** @return array{restored: bool} */
    #[McpTool(
        name: 'assets.restore',
        description: 'Restores a deleted Craft CMS asset whose file was kept on deletion, across all supported sites. Returns restored: false without changes if already active. List deleted assets with criteria {trashed: true, status: null}. siteId selects the loaded variant; it does not limit restoration.',
        annotations: new ToolAnnotations(destructiveHint: true, idempotentHint: true),
    )]
    public function restore(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
    ): array {
        $asset = $this->lifecycle->find(Asset::class, $id, $uid, $siteId, includeTrashed: true, ability: 'save');

        return $this->lifecycle->restore($asset);
    }

    /**
     * @param  array<string, mixed>  $attributes  Proposed built-in attributes using the update schema.
     * @param  array<string, mixed>  $fields  Proposed custom field values keyed by field handle.
     * @return array{valid: bool, scenario: string, errors: array<string, list<string>>}
     */
    #[McpTool(
        name: 'assets.validate',
        description: 'Validates an existing Craft CMS asset under live rules, optionally applying proposed attributes and fields in memory. Does not save or execute file relocation. A valid result does not guarantee a later update succeeds.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function validate(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
        #[Schema(definition: self::UpdateAttributesSchema)]
        array $attributes = [],
        #[Schema(definition: self::FieldsSchema)]
        array $fields = [],
    ): array {
        $asset = $this->lifecycle->find(Asset::class, $id, $uid, $siteId);

        if ($attributes !== [] || $fields !== []) {
            $actor = $this->actor->user();
            $this->authorizeSave($actor, $asset);
            $this->relocation($asset, $attributes, $actor);
            Typecast::configure($asset, $attributes);
            $asset->setFieldValues($fields);
            $this->authorizeSave($actor, $asset);
        }

        return $this->lifecycle->validate($asset);
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
