<?php

declare(strict_types=1);

namespace craft\mcp\capabilities;

use Craft;
use craft\elements\Asset;
use craft\mcp\AssetUploads as McpAssetUploads;
use craft\mcp\Authorization;
use craft\mcp\ElementResourceLinks;
use craft\mcp\elements\adapters\AssetAdapter;
use craft\mcp\McpActor;
use craft\mcp\serializers\ElementSerializer;
use craft\services\Assets as AssetService;
use craft\services\Elements;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ResourceReadException;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;

/**
 * Asset-specific MCP capabilities: creating assets from files, replacing files, uploads, and transform URLs. Assets
 * are listed and managed through the `elements.*` tools.
 *
 * @since 6.0.0
 */
readonly class Assets
{
    private const CreateAttributesSchema = [
        'type' => 'object',
        'properties' => [
            'title' => ['type' => ['string', 'null'], 'description' => 'Asset title.'],
            'alt' => ['type' => ['string', 'null'], 'description' => 'Alternative text.'],
            'enabled' => ['type' => 'boolean', 'description' => 'Whether the asset is enabled.'],
            'siteId' => ['type' => 'integer', 'description' => 'Site ID for the asset element.'],
        ],
        'additionalProperties' => false,
    ];

    private const FieldsSchema = [
        'type' => 'object',
        'description' => 'Custom field values keyed by field handle. Use elements.field-schema for the applicable schema.',
        'additionalProperties' => true,
    ];

    private const FileSchema = [
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
        private ElementSerializer $elementSerializer,
        private Elements $elements,
        private McpAssetUploads $mcpAssetUploads,
    ) {
    }

    /**
     * @param  array<string, mixed>|string  $transform  Named image transform handle or inline transform parameters.
     * @param  int|null  $siteId  Site ID to load the asset in.
     * @param  string|null  $transformer  Reserved for compatibility; custom transformer selection is unavailable in Craft 5.
     * @return array{url: string, mimeType: string, width: int|null, height: int|null}
     */
    #[McpTool(
        name: 'assets.transform-url',
        description: 'Gets a transformed asset URL for previewing. Accepts a named image transform handle or inline parameters such as width, height, mode, and format. Uses Craft 5 transform generation settings and may return a temporary generation URL.',
        annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false),
    )]
    public function transformUrl(
        #[Schema(definition: [
            'anyOf' => [
                ['type' => 'string', 'minLength' => 1],
                ['type' => 'object', 'additionalProperties' => true],
            ],
        ])]
        array | string $transform,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
        ?string $transformer = null,
    ): array {
        $asset = $this->assetAdapter->find($id, $uid, $siteId);

        if (!$asset instanceof Asset || !$this->assetAdapter->canView($this->actor->user(), $asset)) {
            throw new ToolCallException('Asset not found.');
        }

        if ($transformer !== null) {
            throw new ToolCallException('Custom transformer selection is not supported in Craft 5.');
        }

        try {
            $url = $asset->getUrl($transform);
            $width = $asset->getWidth($transform);
            $height = $asset->getHeight($transform);
        } catch (\Throwable $exception) {
            throw new ToolCallException('The asset transform could not be generated.', previous: $exception);
        }

        return [
            'url' => $url,
            'mimeType' => $asset->getMimeType($transform),
            'width' => $width,
            'height' => $height,
        ];
    }

    #[McpTool(
        name: 'assets.create',
        description: 'Creates an asset from a temporary HTTPS file reference, plus folderId or volumeId. Use elements.field-schema to discover custom fields.',
        meta: ['openai/fileParams' => ['file']],
    )]
    public function create(
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
        $actor = $this->actor->user();
        $folder = $this->assetAdapter->targetFolder($folderId, $volumeId);

        if (!$folder || !Authorization::allows($actor, 'uploadAsset', $folder)) {
            throw new ToolCallException('You are not authorized to upload assets to this folder.');
        }

        $this->assertAttributes($attributes);
        $filename = $this->filename($file, $filename);
        $asset = new Asset();
        Craft::configure($asset, $attributes);
        $asset->newFolderId = $folder->id;
        $asset->setVolumeId($folder->volumeId);
        $asset->uploaderId = $actor->id;
        $asset->setFieldValues($fields);
        $this->assetAdapter->authorizeAssetSave($actor, $asset);

        return $this->mcpAssetUploads->consumeFile($file['download_url'], $filename, function(string $path) use ($asset, $filename): array {
            $asset->tempFilePath = $path;
            $asset->setFilename(\craft\helpers\Assets::prepareAssetName($filename));
            $asset->setMimeType(\craft\helpers\FileHelper::getMimeType($path, checkExtension: false));
            $asset->avoidFilenameConflicts = true;
            $asset->sanitizeOnUpload = true;
            $asset->setScenario(Asset::SCENARIO_CREATE);

            if (!$this->elements->saveElement($asset)) {
                throw new ToolCallException(implode("\n", $asset->getErrorSummary(true)) ?: 'Asset could not be created.');
            }

            return ['asset' => $this->elementSerializer->serialize($asset)];
        });
    }

    #[McpTool(
        name: 'assets.replace',
        description: 'Replaces an existing asset file from a temporary HTTPS file reference while preserving its ID and references. Adopts the incoming filename, which may change its URL.',
        annotations: new ToolAnnotations(destructiveHint: true),
        meta: ['openai/fileParams' => ['file']],
    )]
    public function replace(
        #[Schema(minimum: 1)]
        int $assetId,
        #[Schema(definition: self::FileSchema)]
        ?array $file = null,
        #[Schema(minLength: 1, maxLength: 255)]
        ?string $filename = null,
    ): array {
        $actor = $this->actor->user();
        $asset = $this->assets->getAssetById($assetId);

        if (!$asset || !Authorization::assetPermission($actor, $asset, 'replaceFiles', 'replacePeerFiles')
            || !$this->assetAdapter->canView($actor, $asset)) {
            throw new ToolCallException('Asset not found or you are not authorized to replace its file.');
        }

        $filename = $this->filename($file, $filename);

        return $this->mcpAssetUploads->consumeFile($file['download_url'], $filename, function(string $path) use ($asset, $filename): array {
            $asset->sanitizeOnUpload = true;
            $this->assets->replaceAssetFile($asset, $path, \craft\helpers\Assets::prepareAssetName($filename), \craft\helpers\FileHelper::getMimeType($path, checkExtension: false));

            if ($asset->hasErrors()) {
                throw new ToolCallException(implode("\n", $asset->getErrorSummary(true)) ?: 'The asset file could not be replaced.');
            }

            return ['asset' => $this->elementSerializer->serialize($asset)];
        });
    }

    private function filename(?array $file, ?string $filename): string
    {
        if (!$file || !is_string($file['download_url'] ?? null) || !is_string($file['file_id'] ?? null)
            || $file['file_id'] === '') {
            throw new ToolCallException('Provide a file reference with download_url and file_id.');
        }

        $filename ??= $file['file_name'] ?? null;

        if (!is_string($filename) || trim($filename) === '') {
            throw new ToolCallException('Provide filename when the file reference does not include file_name.');
        }

        return $filename;
    }

    private function assertAttributes(array $attributes): void
    {
        $errors = (new \Mcp\Capability\Discovery\SchemaValidator())->validateAgainstJsonSchema($attributes, self::CreateAttributesSchema);

        if ($errors) {
            throw new ToolCallException('Invalid asset attributes.');
        }
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

        if (!$asset || !Authorization::allows($this->actor->user(), 'view', $asset)) {
            throw new ResourceReadException('Asset not found.');
        }

        return ['asset' => $this->elementSerializer->serialize($asset)];
    }
}
