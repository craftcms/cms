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
use CraftCms\Cms\Filesystem\Data\UploadedFile;
use CraftCms\Cms\Filesystem\Data\UploadSessionData;
use CraftCms\Cms\Filesystem\Exceptions\FilesystemException;
use CraftCms\Cms\Mcp\AssetUploads as McpAssetUploads;
use CraftCms\Cms\Mcp\Attributes\RequiresHttp;
use CraftCms\Cms\Mcp\ElementResourceLinks;
use CraftCms\Cms\Mcp\Elements\Adapters\AssetAdapter;
use CraftCms\Cms\Mcp\McpActor;
use CraftCms\Cms\Mcp\Serializers\ElementSerializer;
use CraftCms\Cms\Shared\Exceptions\NotSupportedException;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Typecast;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ResourceReadException;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Asset-specific MCP capabilities: creating assets from files, replacing files, uploads, and transform URLs. Assets
 * are listed and managed through the `elements.*` tools.
 *
 * @since 6.0.0
 */
readonly class Assets
{
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

    private const array FieldsSchema = [
        'type' => 'object',
        'description' => 'Custom field values keyed by field handle. Use elements.field-schema for the applicable schema.',
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
        private AssetAdapter $assetAdapter,
        private AssetService $assets,
        private AssetUploadHandler $assetUploads,
        private ElementSerializer $elementSerializer,
        private Elements $elements,
        private Folders $folders,
        private McpAssetUploads $mcpAssetUploads,
        private Request $request,
    ) {}

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
        $asset = $this->assetAdapter->find($id, $uid, $siteId);

        if (! $asset instanceof Asset || ! $this->assetAdapter->canView($this->actor->user(), $asset)) {
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

    /** @return array{upload: array<string, mixed>} */
    #[McpTool(name: 'assets.upload.prepare', description: 'Starts a short-lived, resumable upload. Provide folderId or volumeId for assets.create, or assetId for assets.replace.')]
    #[RequiresHttp]
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
            $folder = $this->assetAdapter->targetFolder($folderId, $volumeId);

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
        description: 'Creates an asset from exactly one source: a client-provided file reference plus folderId or volumeId, or a completed assets.upload.prepare uploadId. Use file references when the client cannot perform binary transfers. Use elements.field-schema to discover custom fields.',
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
                $folder = $this->assetAdapter->targetFolder($folderId, $volumeId);

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
        $this->assetAdapter->authorizeAssetSave($actor, $asset);

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
