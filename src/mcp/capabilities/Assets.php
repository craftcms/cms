<?php

declare(strict_types=1);

namespace craft\mcp\capabilities;

use Craft;
use craft\elements\Asset;
use craft\elements\conditions\ElementCondition;
use craft\errors\InvalidFsException;
use craft\errors\InvalidSubpathException;
use craft\fields\Assets as AssetsField;
use craft\mcp\AssetUploads as McpAssetUploads;
use craft\mcp\attributes\RequiresHttp;
use craft\mcp\Authorization;
use craft\mcp\ElementResourceLinks;
use craft\mcp\elements\adapters\AssetAdapter;
use craft\mcp\McpActor;
use craft\mcp\schema\CustomFieldSchema;
use craft\mcp\serializers\ElementSerializer;
use craft\mcp\Settings;
use craft\models\VolumeFolder;
use craft\services\Assets as AssetService;
use craft\services\Elements;
use craft\services\Fields;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ResourceReadException;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;

/**
 * Asset-specific MCP capabilities: creating assets from files, replacing files, uploads, and transform URLs. Assets
 * are listed and managed through the `elements_*` tools.
 *
 * @since 5.12.0
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
        'description' => 'Custom field values keyed by field handle. Use elements_field_schema for the applicable schema.',
        'additionalProperties' => true,
    ];

    private const FileSchema = [
        'type' => 'object',
        'description' => 'The file, from exactly one source: download_url, data, path, or upload_id. The filename defaults to file_name, then to the URL or path basename.',
        'properties' => [
            'download_url' => ['type' => 'string', 'format' => 'uri', 'description' => 'Public HTTPS URL to download. Up to 3 redirects are followed.'],
            'data' => ['type' => 'string', 'description' => 'Base64-encoded file contents, or a base64 data URL. Best for small files; use upload_id for larger ones.'],
            'path' => ['type' => 'string', 'description' => 'Absolute path to a file on the machine running the server. Only supported by the stdio server (craft mcp/serve).'],
            'upload_id' => ['type' => 'string', 'description' => 'ID from assets_upload_url, after sending the file to its upload_url. Requires file_name or filename.'],
            'file_id' => ['type' => 'string', 'minLength' => 1],
            'mime_type' => ['type' => 'string'],
            'file_name' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 255],
        ],
        'additionalProperties' => false,
    ];

    public function __construct(
        private McpActor $actor,
        private AssetAdapter $assetAdapter,
        private AssetService $assets,
        private ElementSerializer $elementSerializer,
        private Elements $elements,
        private Fields $fields,
        private McpAssetUploads $mcpAssetUploads,
        private Settings $settings,
    ) {
    }

    /**
     * @param  array<string, mixed>|string  $transform  Named image transform handle or inline transform parameters.
     * @param  int|null  $siteId  Site ID to load the asset in.
     * @param  string|null  $transformer  Reserved for compatibility; custom transformer selection is unavailable in Craft 5.
     * @return array{url: string, mimeType: string, width: int|null, height: int|null}
     */
    #[McpTool(
        name: 'assets_transform_url',
        description: 'Gets a transformed asset URL for previewing. Accepts a named image transform handle or inline parameters such as width, height, mode, and format. Uses Craft 5 transform generation settings and may return a temporary generation URL.',
        annotations: new ToolAnnotations(readOnlyHint: true),
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
        name: 'assets_create',
        description: 'Creates an asset from a file reference. Target exactly one of: folderId, volumeId (its root folder), or fieldId (an Assets field’s default upload location; pass elementId, and attributes.siteId if needed, for the element the field belongs to so dynamic subpaths resolve). Use elements_field_schema to discover custom fields.',
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
        #[Schema(minimum: 1)]
        ?int $fieldId = null,
        #[Schema(minimum: 1)]
        ?int $elementId = null,
    ): array {
        $actor = $this->actor->user();
        $selectionCondition = null;

        if ($fieldId !== null) {
            if ($folderId !== null || $volumeId !== null) {
                throw new ToolCallException('Provide exactly one of: folderId, volumeId, fieldId.');
            }

            [$folder, $selectionCondition] = $this->fieldUploadFolder($fieldId, $elementId, $attributes['siteId'] ?? null);
        } else {
            if ($elementId !== null) {
                throw new ToolCallException('elementId is only used with fieldId.');
            }

            $folder = $this->assetAdapter->targetFolder($folderId, $volumeId);
        }

        if (!$folder || !$this->canUploadTo($folder)) {
            throw new ToolCallException('You are not authorized to upload assets to this folder.');
        }

        $this->assertAttributes($attributes);

        if (!$file) {
            throw new ToolCallException('Provide a file reference.');
        }

        $asset = new Asset();
        Craft::configure($asset, $attributes);
        $asset->newFolderId = $folder->id;
        $asset->setVolumeId($folder->volumeId);
        $asset->uploaderId = $actor->id;
        $fieldValues = (new CustomFieldSchema())->prepareValues($asset, $fields);
        $asset->setFieldValues($fieldValues);
        $this->assetAdapter->authorizeAssetSave($actor, $asset);

        return $this->mcpAssetUploads->consumeFile($file, $filename, function(string $path, string $filename) use ($actor, $asset, $fieldValues, $folder, $selectionCondition): array {
            $filename = \craft\helpers\Assets::prepareAssetName($filename);
            $tempFolder = $selectionCondition ? $this->assets->getUserTemporaryUploadFolder($actor) : null;
            $staged = $tempFolder && $tempFolder->id !== $folder->id;

            if ($staged) {
                $filename = $this->assets->getNameReplacementInFolder($filename, $folder->id);
            }

            // Like the control panel, stage files for fields with selection conditions in the user’s temporary
            // folder, so rejected files never reach the destination volume
            if ($staged) {
                $asset->newFolderId = $tempFolder->id;
                $asset->setVolumeId($tempFolder->volumeId);
                $asset->title ??= \craft\helpers\Assets::filename2Title(pathinfo($filename, PATHINFO_FILENAME));
            }

            $asset->tempFilePath = $path;
            $asset->setFilename($staged ? uniqid('asset', true) . '.' . pathinfo($filename, PATHINFO_EXTENSION) : $filename);
            $asset->setMimeType(\craft\helpers\FileHelper::getMimeType($path, checkExtension: false));
            $asset->avoidFilenameConflicts = true;
            $asset->sanitizeOnUpload = true;
            $asset->setScenario(Asset::SCENARIO_CREATE);

            if (!$this->elements->saveElement($asset)) {
                throw new ToolCallException(implode("\n", $asset->getErrorSummary(true)) ?: 'Asset could not be created.');
            }

            if ($selectionCondition) {
                // Match the asset as it will be once it’s moved to its upload location
                $candidate = $asset;

                if ($staged) {
                    $candidate = clone $asset;
                    $candidate->setFilename($filename);
                    $candidate->folderId = $folder->id;
                    $candidate->setVolumeId($folder->volumeId);
                }

                if (!$selectionCondition->matchElement($candidate)) {
                    $this->elements->deleteElement($asset, true);
                    throw new ToolCallException("$filename isn’t selectable for this field.");
                }
            }

            if ($staged) {
                $asset->newFilename = $filename;
                $asset->newFolderId = $folder->id;
                $asset->setScenario(Asset::SCENARIO_MOVE);

                if (!$this->elements->saveElement($asset)) {
                    $errors = implode("\n", $asset->getErrorSummary(true));
                    $this->elements->deleteElement($asset, true);
                    throw new ToolCallException($errors ?: 'Asset could not be moved to its upload location.');
                }

                // The staged asset was saved with the temporary volume’s field layout, so save its field values now
                if ($fieldValues) {
                    $asset->setVolumeId($folder->volumeId);
                    $asset->setFieldValues($fieldValues);
                    $asset->setScenario(Asset::SCENARIO_DEFAULT);

                    if (!$this->elements->saveElement($asset)) {
                        $errors = implode("\n", $asset->getErrorSummary(true));
                        $this->elements->deleteElement($asset, true);
                        throw new ToolCallException($errors ?: 'Asset could not be created.');
                    }
                }
            }

            return ['asset' => $this->elementSerializer->serialize($asset)];
        });
    }

    #[McpTool(
        name: 'assets_replace',
        description: 'Replaces an existing asset file from a file reference while preserving its ID and references. Adopts the incoming filename, which may change its URL.',
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

        if (!$file) {
            throw new ToolCallException('Provide a file reference.');
        }

        return $this->mcpAssetUploads->consumeFile($file, $filename, function(string $path, string $filename) use ($asset): array {
            $asset->sanitizeOnUpload = true;
            $this->assets->replaceAssetFile($asset, $path, \craft\helpers\Assets::prepareAssetName($filename), \craft\helpers\FileHelper::getMimeType($path, checkExtension: false));

            if ($asset->hasErrors()) {
                throw new ToolCallException(implode("\n", $asset->getErrorSummary(true)) ?: 'The asset file could not be replaced.');
            }

            return ['asset' => $this->elementSerializer->serialize($asset)];
        });
    }

    /**
     * @return array{upload_id: string, upload_url: string, method: string, expires_at: string, max_size: int}
     */
    #[McpTool(
        name: 'assets_upload_url',
        description: 'Gets a single-use URL for uploading a local or large file. Send the raw file bytes as the body of a PUT request to upload_url before expires_at (for example, curl -T ./photo.jpg "<upload_url>"), then pass {"upload_id": "...", "file_name": "photo.jpg"} as the file for assets_create or assets_replace. The file name is required, since it can’t be derived from the upload.',
    )]
    #[RequiresHttp]
    public function uploadUrl(): array
    {
        return $this->mcpAssetUploads->createUpload($this->actor->user(), $this->settings->url($this->settings->route));
    }

    /**
     * Resolves an Assets field’s default upload location, the same way the control panel does when uploading
     * files to the field.
     *
     * @return array{VolumeFolder, ElementCondition|null}
     */
    private function fieldUploadFolder(int $fieldId, ?int $elementId, ?int $siteId): array
    {
        $field = $this->fields->getFieldById($fieldId);

        if (!$field instanceof AssetsField) {
            throw new ToolCallException('fieldId must be the ID of an Assets field.');
        }

        // Check the field’s volume before resolving its dynamic path, which can create folders
        $volume = $field->_volumeBySourceKey($field->restrictLocation ? $field->restrictedLocationSource : $field->defaultUploadLocationSource);

        if ($volume && !$this->actor->user()->can("saveAssets:$volume->uid")) {
            throw new ToolCallException('You are not authorized to upload assets to this folder.');
        }

        $element = null;

        if ($elementId !== null) {
            $element = $this->elements->getElementById($elementId, null, $siteId);

            if (!$element || !Authorization::allows($this->actor->user(), 'view', $element)) {
                throw new ToolCallException('Element not found.');
            }
        }

        try {
            $folder = $this->assets->getFolderById($field->resolveDynamicPathToFolderId($element));
        } catch (InvalidFsException|InvalidSubpathException $exception) {
            throw new ToolCallException($exception->getMessage(), previous: $exception);
        }

        $condition = $field->getSelectionCondition();

        if ($condition instanceof ElementCondition) {
            $condition->referenceElement = $element;
        } else {
            $condition = null;
        }

        return [$folder, $condition];
    }

    /** Whether the actor may upload to a folder, which may be their temporary upload folder. */
    private function canUploadTo(VolumeFolder $folder): bool
    {
        if (!$folder->volumeId) {
            return $folder->id === $this->assets->getUserTemporaryUploadFolder($this->actor->user())->id;
        }

        return Authorization::allows($this->actor->user(), 'uploadAsset', $folder);
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
