<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Elements;

use Closure;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Asset\Actions\CopyReferenceTag;
use CraftCms\Cms\Asset\Actions\CopyUrl;
use CraftCms\Cms\Asset\Actions\DeleteAssets;
use CraftCms\Cms\Asset\Actions\DownloadAssetFile;
use CraftCms\Cms\Asset\Actions\EditImage;
use CraftCms\Cms\Asset\Actions\MoveAssets;
use CraftCms\Cms\Asset\Actions\PreviewAsset;
use CraftCms\Cms\Asset\Actions\RenameFile;
use CraftCms\Cms\Asset\Actions\ReplaceFile;
use CraftCms\Cms\Asset\Actions\ShowInFolder;
use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Asset\AssetTransformers;
use CraftCms\Cms\Asset\Concerns\LegacyConstants;
use CraftCms\Cms\Asset\Conditions\AssetCondition;
use CraftCms\Cms\Asset\Data\AssetTransformResult;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Enums\FileKind;
use CraftCms\Cms\Asset\Events\AssetFileHandling;
use CraftCms\Cms\Asset\Events\AssetUrlDefined;
use CraftCms\Cms\Asset\Events\AssetUrlResolving;
use CraftCms\Cms\Asset\Exceptions\AssetException;
use CraftCms\Cms\Asset\Exceptions\AssetTransformException;
use CraftCms\Cms\Asset\Exceptions\FileException;
use CraftCms\Cms\Asset\Exceptions\ImageTransformException;
use CraftCms\Cms\Asset\Exceptions\VolumeException;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Validation\AssetRules;
use CraftCms\Cms\Asset\Validation\Rules\AssetLocationRule;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Actions\Restore;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\CurrentElementIndex;
use CraftCms\Cms\Element\Data\EagerLoadPlan;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementAttributeRenderer;
use CraftCms\Cms\Element\Enums\MenuItemType;
use CraftCms\Cms\Element\Queries\AssetQuery;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\Exceptions\QueryAbortedException;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Filesystem\Exceptions\FilesystemException;
use CraftCms\Cms\Filesystem\Filesystems\Filesystem;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Gql\Interfaces\Elements\Asset as AssetInterface;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Http\ViewModels\AssetEditViewModel;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\ImageHelper;
use CraftCms\Cms\Image\ImageTransformHelper;
use CraftCms\Cms\Search\SearchQuery;
use CraftCms\Cms\Search\SearchQueryTerm;
use CraftCms\Cms\Search\SearchQueryTermGroup;
use CraftCms\Cms\Shared\Exceptions\NotSupportedException;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Assets as AssetsService;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Facades\ElementSources;
use CraftCms\Cms\Support\Facades\Filesystems;
use CraftCms\Cms\Support\Facades\Folders;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Images;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\Facades\Search;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Template;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;
use CraftCms\Cms\User\Elements\User;
use CraftCms\RulesetValidation\Attributes\Ruleset;
use DateTimeInterface;
use DOMDocument;
use DOMXPath;
use Exception;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Query\Builder;
use Illuminate\Filesystem\LocalFilesystemAdapter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Imagick;
use InvalidArgumentException;
use League\Flysystem\UnableToDeleteFile;
use Override;
use RuntimeException;
use Stringable;
use Throwable;
use Twig\Markup;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\currentUserElement;
use function CraftCms\Cms\t;

/**
 * @property int|null $height the image height
 * @property int|null $width the image width
 * @property int|null $volumeId the volume ID
 * @property string $filename the filename (with extension)
 * @property string|array{x: float, y: float}|null $focalPoint the focal point represented as an array with `x` and `y` keys, or null if it’s not an image
 * @property-read Markup|null $img an `<img>` tag based on this asset
 * @property-read VolumeFolder $folder the asset’s volume folder
 * @property-read Volume $volume the asset’s volume
 * @property-read bool $hasFocalPoint whether a user-defined focal point is set on the asset
 * @property-read string $extension the file extension
 * @property-read string $path the asset's path in the volume
 * @property-write string $transformSource
 * @property-read null|string $dimensions
 * @property-read string $copyOfFile
 * @property-read string[] $cacheTags
 * @property-read string $contents
 * @property-read bool $hasCheckeredThumb
 * @property-read bool $supportsImageEditor
 * @property-read list<array{label: string, url: string}> $previewTargets
 * @property-read string $titleTranslationKey
 * @property-read null|string $titleTranslationDescription
 * @property-read string $dataUrl
 * @property-read bool $isTitleTranslatable
 * @property-read string $previewHtml
 * @property-read string $imageTransformSourcePath
 * @property User|null $uploader
 * @property-read resource $stream
 * @property-read string $gqlTypeName
 * @property-read string|null $mimeType the file’s MIME type, if it can be determined
 *
 * @phpstan-type TransformConfig array<string, bool|float|int|string|array<string, bool|float|int|string|null>|null>
 * @phpstan-type SourcePathInfo array{uri: string, folderId: int, hasChildren: bool, canView: bool, canCreate: bool, canMoveSubItems: bool, key: string, label: string, icon?: string, handle?: string|null, criteria?: array{folderId: int|null}, canRename?: bool, canMove?: bool, canDelete?: bool}
 * @phpstan-type SourceInfo array{key: string, label: string|null, hasThumbs: bool, criteria: array{folderId: int|null, uploaderId?: int|null}, defaultSort: array{string, string}, defaultSourcePath: list<SourcePathInfo>|null, data: array{volume-handle: string|false, folder-id: int|null, can-upload: bool, can-move-to: bool, can-move-peer-files-to?: bool, fs-type: class-string}}
 *
 * @phpstan-import-type EagerLoadingMap from ElementInterface
 */
#[Ruleset(AssetRules::class)]
class Asset extends Element
{
    use LegacyConstants;

    // Location error codes
    // -------------------------------------------------------------------------

    public const string ERROR_DISALLOWED_EXTENSION = 'disallowed_extension';

    public const string ERROR_FILENAME_CONFLICT = 'filename_conflict';

    private static string $_displayName;

    /**
     * @var bool Whether this asset represents a folder.
     *
     * @internal
     */
    #[AllowedInSandbox]
    public bool $isFolder = false;

    /**
     * @var list<array<string, bool|int|string|null>>|null The source path, if this represents a folder.
     *
     * @internal
     */
    #[AllowedInSandbox]
    public ?array $sourcePath = null;

    /**
     * @var int|null Folder ID
     */
    #[AllowedInSandbox]
    public ?int $folderId = null;

    /**
     * @var int|null The ID of the user who first added this asset (if known)
     */
    #[AllowedInSandbox]
    public ?int $uploaderId = null;

    /**
     * @var string|null Folder path
     */
    #[AllowedInSandbox]
    public ?string $folderPath = null;

    /**
     * @var string|null Kind
     */
    #[AllowedInSandbox]
    public ?string $kind = null;

    /**
     * @var string|null Alternative text
     */
    #[AllowedInSandbox]
    public ?string $alt = null;

    /**
     * @var int|null Size
     */
    #[AllowedInSandbox]
    public ?int $size = null;

    /**
     * @var bool|null Whether the file was kept around when the asset was deleted
     */
    #[AllowedInSandbox]
    public ?bool $keptFile = null;

    /**
     * @var DateTimeInterface|null Date modified
     */
    #[AllowedInSandbox]
    public ?DateTimeInterface $dateModified = null;

    /**
     * @var string|null New file location
     */
    #[AllowedInSandbox]
    public ?string $newLocation = null;

    /**
     * @var string|null Location error code
     *
     * @see AssetLocationRule
     */
    #[AllowedInSandbox]
    public ?string $locationError = null;

    /**
     * @var string|null New filename
     */
    public ?string $newFilename = null;

    /**
     * @var int|null New folder ID
     */
    public ?int $newFolderId = null;

    /**
     * @var string|null The temp file path
     */
    public ?string $tempFilePath = null;

    /**
     * @var bool Whether the asset should avoid filename conflicts when saved.
     */
    public bool $avoidFilenameConflicts = false;

    /**
     * @var string|null The suggested filename in case of a conflict.
     */
    public ?string $suggestedFilename = null;

    /**
     * @var string|null The filename that was used that caused a conflict.
     */
    public ?string $conflictingFilename = null;

    /**
     * @var bool Whether the asset was deleted along with its volume
     *
     * @see beforeDelete()
     */
    public bool $deletedWithVolume = false;

    /**
     * @var bool Whether the associated file should be preserved if the asset record is deleted.
     *
     * @see beforeDelete()
     * @see afterDelete()
     */
    public bool $keepFileOnDelete = false;

    /**
     * @var bool|null Whether the associated file should be sanitized on upload, if it's an image. Defaults to `true`,
     *                unless it’s a control panel request and <config4:sanitizeCpImageUploads> is disabled.
     *
     * @see afterSave()
     */
    public ?bool $sanitizeOnUpload = null;

    /**
     * @var int|null Volume ID
     */
    private ?int $_volumeId = null;

    /**
     * @var string Filename
     */
    private string $_filename;

    private ?string $_mimeType = null;

    /**
     * @var int|null Width
     */
    private ?int $_width = null;

    /**
     * @var int|null Height
     */
    private ?int $_height = null;

    /**
     * @var array{x: float, y: float}|null Focal point
     */
    private ?array $_focalPoint = null;

    private ?Volume $_volume = null;

    private ?User $_uploader = null;

    private ?int $_oldVolumeId = null;

    public function __construct($config = [])
    {
        // alt='' actually means something, so we should preserve it.
        $alt = Arr::pull($config, 'alt');
        if ($alt !== null) {
            $this->alt = $alt;
        }

        parent::__construct($config);

        if (isset($this->alt)) {
            $this->alt = trim($this->alt);
        }

        $this->_oldVolumeId = $this->_volumeId;
    }

    #[Override]
    public static function displayName(): string
    {
        return self::$_displayName ??= self::isFolderIndex()
            ? t('Folder')
            : t('Asset');
    }

    #[Override]
    public static function objectTemplateSuggestions(): array
    {
        return [
            ...parent::objectTemplateSuggestions(),
            'filename' => t('Filename'),
            'extension' => t('File Extension'),
            'kind' => t('File Kind'),
            'width' => t('Image Width'),
            'height' => t('Image Height'),
            'alt' => t('Alternative Text'),
            'volume.handle' => t('Volume Handle'),
            'uploader.username' => t('Uploader Username'),
        ];
    }

    #[Override]
    public static function lowerDisplayName(): string
    {
        return t('asset');
    }

    #[Override]
    public static function pluralDisplayName(): string
    {
        return t('Assets');
    }

    #[Override]
    public static function pluralLowerDisplayName(): string
    {
        return t('assets');
    }

    public static function refHandle(): string
    {
        return 'asset';
    }

    #[Override]
    public static function editViewModelClass(): string
    {
        return AssetEditViewModel::class;
    }

    #[Override]
    public static function hasTitles(): bool
    {
        return true;
    }

    #[Override]
    public static function hasThumbs(): bool
    {
        return true;
    }

    #[Override]
    public static function isLocalized(): bool
    {
        return true;
    }

    /**
     * @return AssetQuery The newly created [[AssetQuery]] instance.
     */
    #[Override]
    public static function find(): AssetQuery
    {
        return new AssetQuery;
    }

    /**
     * @return AssetCondition
     */
    #[Override]
    public static function createCondition(): ElementConditionInterface
    {
        return new AssetCondition(self::class);
    }

    /** @return array{elementType?: class-string<ElementInterface>, map: list<array{elementType?: class-string<ElementInterface>, source: int, target: int}>, criteria?: array<string, bool|int|string|null>, createElement?: callable}|list<array{elementType?: class-string<ElementInterface>, map: list<array{elementType?: class-string<ElementInterface>, source: int, target: int}>, criteria?: array<string, bool|int|string|null>, createElement?: callable}>|false|null */
    #[Override]
    public static function eagerLoadingMap(array $sourceElements, string $handle): array|null|false
    {
        if ($handle === 'uploader') {
            // Get the source element IDs
            $sourceElementIds = array_map(fn (ElementInterface $element) => $element->id, $sourceElements);

            $map = DB::table(Table::ASSETS)
                ->select(['id as source', 'uploaderId as target'])
                ->whereIn('id', $sourceElementIds)
                ->whereNotNull('uploaderId')
                ->get()
                ->map(fn (object $row) => (array) $row)
                ->all();

            return [
                'elementType' => User::class,
                'map' => $map,
            ];
        }

        return parent::eagerLoadingMap($sourceElements, $handle);
    }

    #[Override]
    public function setEagerLoadedElements(string $handle, array $elements, EagerLoadPlan $plan): void
    {
        if ($plan->handle === 'uploader') {
            /** @var User|null $uploader */
            $uploader = $elements[0] ?? null;
            $this->setUploader($uploader);
        } else {
            parent::setEagerLoadedElements($handle, $elements, $plan);
        }
    }

    /**
     * Returns the GraphQL type name that assets should use, based on their volume.
     */
    public static function gqlTypeName(Volume $volume): string
    {
        return sprintf('%s_Asset', $volume->handle);
    }

    #[Override]
    public static function baseGqlType(): Type
    {
        return AssetInterface::getType();
    }

    /** @return list<string> */
    #[Override]
    public static function gqlScopesByContext(mixed $context): array
    {
        return ['volumes.'.$context->uid];
    }

    /** @return list<SourceInfo> */
    #[Override]
    protected static function defineSources(string $context): array
    {
        $sources = [];
        $user = currentUserElement();
        $volumeIds = $context === ElementSources::CONTEXT_INDEX
            ? Volumes::getViewableVolumeIds()
            : Volumes::getAllVolumeIds();

        foreach ($volumeIds as $volumeId) {
            $folder = Folders::getRootFolderByVolumeId($volumeId);
            $sources[] = self::_assembleSourceInfoForFolder($folder, $user);
        }

        // Add the Temporary Uploads location
        if (
            $context !== ElementSources::CONTEXT_SETTINGS &&
            ! app()->runningInConsole()
        ) {
            $temporaryUploadFolder = AssetsService::getUserTemporaryUploadFolder();
            $temporaryUploadVolume = $temporaryUploadFolder->getVolume();
            $sources[] = [
                'key' => 'temp',
                'label' => t('Temporary Uploads'),
                'hasThumbs' => true,
                'criteria' => ['folderId' => $temporaryUploadFolder->id],
                'defaultSort' => ['dateCreated', 'desc'],
                'data' => [
                    'volume-handle' => false,
                    'folder-id' => $temporaryUploadFolder->id,
                    'can-upload' => true,
                    'can-move-to' => false,
                    'can-move-peer-files-to' => false,
                    'fs-type' => $temporaryUploadVolume->sourceFilesystemType(),
                ],
            ];
        }

        return $sources;
    }

    /** @return SourceInfo|null */
    public static function findSource(string $sourceKey, ?string $context): ?array
    {
        if (preg_match('/^volume:[\w\-]+(?:\/.+)?\/folder:([\w\-]+)$/', $sourceKey, $match)) {
            $folder = Folders::getFolderByUid($match[1]);
            if ($folder) {
                $source = self::_assembleSourceInfoForFolder($folder, currentUserElement());
                $source['keyPath'] = $sourceKey;

                return $source;
            }
        }

        return null;
    }

    /** @return list<array<string, bool|int|string|array{folderId: int|null}|null>>|null */
    public static function sourcePath(string $sourceKey, string $stepKey, ?string $context): ?array
    {
        if (! preg_match('/^folder:([\w\-]+)$/', $stepKey, $match)) {
            return null;
        }

        $folder = Folders::getFolderByUid($match[1]);

        if (! $folder) {
            return null;
        }

        $path = [$folder->getSourcePathInfo()];

        while ($parent = $folder->getParent()) {
            array_unshift($path, $parent->getSourcePathInfo());
            $folder = $parent;
        }

        return $path;
    }

    #[Override]
    protected static function defineFieldLayouts(?string $source): array
    {
        if ($source !== null && preg_match('/^volume:(.+)$/', $source, $matches)) {
            $volumes = array_filter([
                Volumes::getVolumeByUid($matches[1]),
            ]);
        } else {
            $volumes = Volumes::getAllVolumes()->all();
        }

        return array_map(fn (Volume $volume) => $volume->getFieldLayout(), $volumes);
    }

    /** @return list<class-string|array{type: class-string, label?: string, restorableElementsOnly?: bool}> */
    #[Override]
    protected static function defineActions(string $source): array
    {
        $actions = [];

        if (preg_match('/^volume:([a-z0-9\-]+)/', $source, $matches)) {
            $volume = Volumes::getVolumeByUid($matches[1]);
        } elseif (preg_match('/^folder:([a-z0-9\-]+)/', $source, $matches)) {
            $folder = Folders::getFolderByUid($matches[1]);
            $volume = $folder?->getVolume();
        }

        // Only match the first folder ID - ignore nested folders
        if (isset($volume)) {
            $isTemp = $volume->isTemporary();

            $actions[] = [
                'type' => PreviewAsset::class,
                'label' => t('Preview file'),
            ];

            // Download
            $actions[] = DownloadAssetFile::class;

            if ($isTemp || Gate::check("replaceFiles:$volume->uid")) {
                // Rename/Replace File
                $actions[] = RenameFile::class;
                $actions[] = ReplaceFile::class;
            }

            // Copy URL
            if ($volume->sourceHasUrls()) {
                $actions[] = CopyUrl::class;
            }

            // Show in folder
            $query = app(CurrentElementIndex::class)->isActive()
                ? app(CurrentElementIndex::class)->query()
                : null;
            if (
                $query instanceof AssetQuery &&
                isset($query->search) &&
                $query->includeSubfolders
            ) {
                $actions[] = ShowInFolder::class;
            }

            // Copy Reference Tag
            $actions[] = CopyReferenceTag::class;

            // Edit Image
            if ($isTemp || Gate::check("editImages:$volume->uid")) {
                $actions[] = EditImage::class;
            }

            // Move
            $actions[] = MoveAssets::class;

            // Restore
            $actions[] = [
                'type' => Restore::class,
                'restorableElementsOnly' => true,
            ];

            // Delete
            if (Gate::check("deletePeerAssets:$volume->uid")) {
                $actions[] = DeleteAssets::class;
            }
        }

        return $actions;
    }

    #[Override]
    protected static function defineSearchableAttributes(): array
    {
        return ['filename', 'extension', 'kind', 'alt'];
    }

    /** @return array<int|string, string|array{label: string, orderBy: string, defaultDir: string}> */
    #[Override]
    public static function sortOptions(): array
    {
        if (self::isFolderIndex()) {
            return [
                'title' => t('Folder'),
            ];
        }

        return parent::sortOptions();
    }

    /** @return array<int|string, string|array{label: string, orderBy: string, defaultDir: string}> */
    #[Override]
    protected static function defineSortOptions(): array
    {
        return [
            'title' => t('Title'),
            'filename' => t('Filename'),
            'size' => t('File Size'),
            'kind' => t('File Kind'),
            [
                'label' => t('File Modification Date'),
                'orderBy' => 'dateModified',
                'defaultDir' => 'desc',
            ],
            [
                'label' => t('Date Uploaded'),
                'orderBy' => 'dateCreated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => t('Date Updated'),
                'orderBy' => 'dateUpdated',
                'defaultDir' => 'desc',
            ],
            'width' => t('Width'),
            'height' => t('Height'),
        ];
    }

    /** @return array<string, array<string, string>> */
    #[Override]
    public static function tableAttributes(): array
    {
        if (self::isFolderIndex()) {
            return [];
        }

        return parent::tableAttributes();
    }

    /** @return array<string, array{label: string, icon?: string}> */
    #[Override]
    protected static function defineTableAttributes(): array
    {
        $attributes = array_merge(parent::defineTableAttributes(), [
            'dateCreated' => ['label' => t('Date Uploaded')],
            'filename' => ['label' => t('Filename')],
            'size' => ['label' => t('File Size')],
            'kind' => ['label' => t('File Kind')],
            'imageSize' => ['label' => t('Dimensions')],
            'width' => ['label' => t('Image Width')],
            'height' => ['label' => t('Image Height')],
            'alt' => ['label' => t('Alternative Text')],
            'location' => ['label' => t('Location')],
            'link' => ['label' => t('Link'), 'icon' => 'world'],
            'dateModified' => ['label' => t('File Modified Date')],
            'uploader' => ['label' => t('Uploaded By')],
        ]);

        // Hide Author from Craft Solo
        if (Edition::get() === Edition::Solo) {
            unset($attributes['uploader']);
        }

        return $attributes;
    }

    #[Override]
    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'filename',
            'size',
            'dateModified',
            'uploader',
            'link',
        ];
    }

    #[Override]
    protected static function prepElementQueryForTableAttribute(ElementQueryInterface $elementQuery, string $attribute): void
    {
        if ($attribute === 'uploader') {
            $elementQuery->andWith('uploader');
        } else {
            parent::prepElementQueryForTableAttribute($elementQuery, $attribute);
        }
    }

    /** @return array<string, array{label: string, placeholder: Closure}> */
    #[Override]
    protected static function defineCardAttributes(): array
    {
        $attributes = array_merge($parentAttributes = parent::defineCardAttributes(), [
            'dateCreated' => [
                'label' => t('Date Uploaded'),
                'placeholder' => $parentAttributes['dateCreated']['placeholder'],
            ],
            'filename' => [
                'label' => t('Filename'),
                'placeholder' => fn () => t('placeholder').'.png',
            ],
            'size' => [
                'label' => t('File Size'),
                'placeholder' => fn () => '2KB',
            ],
            'kind' => [
                'label' => t('File Kind'),
                'placeholder' => fn () => t('Image'),

            ],
            'imageSize' => [
                'label' => t('Dimensions'),
                'placeholder' => fn () => '700x500',
            ],
            'width' => [
                'label' => t('Image Width'),
                'placeholder' => fn () => '700px',
            ],
            'height' => [
                'label' => t('Image Height'),
                'placeholder' => fn () => '500px',
            ],
            'location' => [
                'label' => t('Location'),
                'placeholder' => fn () => t('Volume'),
            ],
            'link' => [
                'label' => t('Link'),
                'placeholder' => fn () => app(ElementAttributeRenderer::class)->linkAttributeHtml(null),
            ],
            'dateModified' => [
                'label' => t('File Modified Date'),
                'placeholder' => fn () => now()->subDays(14),
            ],
            'uploader' => [
                'label' => t('Uploaded By'),
                'placeholder' => fn () => ($uploader = currentUserElement()) ? app(ElementHtml::class)->elementChipHtml($uploader) : '',
            ],
        ]);

        // Hide Author from Craft Solo
        if (Edition::get() === Edition::Solo) {
            unset($attributes['uploader']);
        }

        return $attributes;
    }

    /** @param array{value: string, placeholder: mixed} $attribute */
    #[Override]
    public static function attributePreviewHtml(array $attribute): mixed
    {
        return match ($attribute['value']) {
            'uploader' => $attribute['placeholder'],
            default => parent::attributePreviewHtml($attribute),
        };
    }

    #[Override]
    public static function indexElements(ElementQueryInterface $elementQuery, ?string $sourceKey): array
    {
        $assets = [];
        $originalLimit = $elementQuery->limit;

        // Include folders in the results?
        /** @var AssetQuery $elementQuery */
        if (self::_includeFoldersInIndexElements($elementQuery, $sourceKey, $queryFolder)) {
            $folderQuery = self::_createFolderQueryForIndex($elementQuery, $queryFolder);
            $totalFolders = $folderQuery->count();

            if ($totalFolders > (int) $elementQuery->offset) {
                $source = ElementSources::findSource(self::class, $sourceKey);
                if (isset($source['criteria']['folderId'])) {
                    $baseFolder = Folders::getFolderById($source['criteria']['folderId']);
                } else {
                    $baseFolder = Folders::getRootFolderByVolumeId($queryFolder->getVolume()->id);
                }
                $baseSourcePathStep = $baseFolder->getSourcePathInfo();

                $folderQuery
                    ->when($elementQuery->getOffset(), fn ($query) => $query->offset($elementQuery->getOffset()))
                    ->when($elementQuery->getLimit(), fn ($query) => $query->limit($elementQuery->getLimit()));

                $folders = $folderQuery->get()->map(fn (object $result) => new VolumeFolder((array) $result))->all();

                $foldersByPath = Arr::keyBy($folders, fn (VolumeFolder $folder) => rtrim((string) $folder->path, '/'));

                foreach ($folders as $folder) {
                    $sourcePath = [$baseSourcePathStep];
                    $path = rtrim($baseFolder->path ?? '', '/');
                    $pathSegs = Arr::whereNotEmpty(explode('/', Str::chopStart($folder->path, $baseFolder->path ?? '')));
                    foreach ($pathSegs as $i => $seg) {
                        $path .= ($path !== '' ? '/' : '').$seg;
                        if (isset($foldersByPath[$path])) {
                            $stepFolder = $foldersByPath[$path];
                        } else {
                            $stepFolder = Folders::findFolder([
                                'volumeId' => $queryFolder->volumeId,
                                'path' => "$path/",
                            ]);
                            if (! $stepFolder) {
                                $stepFolder = Folders::ensureFolderByFullPathAndVolume($path, $queryFolder->getVolume());
                            }
                            $foldersByPath[$path] = $stepFolder;
                        }

                        if ($i < count($pathSegs) - 1) {
                            $stepFolder->setHasChildren(true);
                        }
                        $sourcePath[] = $stepFolder->getSourcePathInfo();
                    }

                    $path = rtrim((string) $folder->path, '/');
                    $path = Str::chopStart($path, $queryFolder->path ?? '');
                    $path = Str::beforeLast($path, $folder->name);

                    $assets[] = new self([
                        'isFolder' => true,
                        'volumeId' => $queryFolder->volumeId,
                        'folderId' => $folder->id,
                        'folderPath' => $path,
                        'title' => $folder->name,
                        'uiLabelPath' => Arr::whereNotEmpty(explode('/', $path)),
                        'sourcePath' => $sourcePath,
                    ]);
                }
            }

            // Is there room for any normal assets as well?
            $totalAssets = count($assets);
            if ($totalAssets < $elementQuery->limit) {
                $elementQuery->offset(max($elementQuery->offset - $totalFolders, 0));
                $elementQuery->limit($elementQuery->limit - $totalAssets);
            }
        }

        // if it's a 'foldersOnly' request, or we have enough folders to hit the query limit,
        // return the folders directly
        if (
            self::isFolderIndex() ||
            count($assets) === $originalLimit
        ) {
            return $assets;
        }

        // otherwise merge in the resulting assets
        return array_merge($assets, $elementQuery->all());
    }

    #[Override]
    public static function indexElementCount(ElementQueryInterface $elementQuery, ?string $sourceKey): int
    {
        $count = 0;

        /** @var AssetQuery $elementQuery */
        if (self::_includeFoldersInIndexElements($elementQuery, $sourceKey, $queryFolder)) {
            try {
                $count += self::_createFolderQueryForIndex($elementQuery, $queryFolder)->count();
            } catch (QueryAbortedException) {
                return 0;
            }
        }

        if (! self::isFolderIndex()) {
            $count += parent::indexElementCount($elementQuery, $sourceKey);
        }

        return $count;
    }

    private static function _includeFoldersInIndexElements(AssetQuery $assetQuery, ?string $sourceKey, ?VolumeFolder &$queryFolder = null): bool
    {
        if (
            ! request()->input('showFolders') ||
            ! str_starts_with((string) $sourceKey, 'volume:') ||
            ! is_numeric($assetQuery->folderId)
        ) {
            return false;
        }

        if ($queryFolder === null) {
            $queryFolder = Folders::getFolderById($assetQuery->folderId);
            if (! $queryFolder) {
                return false;
            }
        }

        if ($queryFolder->getVolume()->isTemporary()) {
            return false;
        }

        if ($assetQuery->search) {
            $assetQuery->search = $searchQuery = Search::normalizeSearchQuery($assetQuery->search);
            $tokens = $searchQuery->getTokens();
            if (count($tokens) !== 1 || ! self::_validateSearchTermForIndex(reset($tokens))) {
                return false;
            }
        }

        return true;
    }

    private static function _validateSearchTermForIndex(SearchQueryTerm|SearchQueryTermGroup $token): bool
    {
        if ($token instanceof SearchQueryTermGroup) {
            return array_all($token->terms, fn ($term) => self::_validateSearchTermForIndex($term));
        }

        /** @var SearchQueryTerm $token */
        return ! $token->exclude && ! $token->attribute;
    }

    /**
     * @throws QueryAbortedException
     */
    private static function _createFolderQueryForIndex(AssetQuery $assetQuery, ?VolumeFolder $queryFolder = null): Builder
    {
        if (
            is_array($assetQuery->orders) &&
            is_string($firstOrderByCol = $assetQuery->orders[0]['column']) &&
            in_array($firstOrderByCol, ['title', 'filename'])
        ) {
            $sortDir = $assetQuery->orders[0]['direction'] === 'asc' ? 'asc' : 'desc';
        } else {
            $sortDir = 'asc';
        }

        $query = Folders::createFolderQuery()
            ->orderBy('name', $sortDir);

        if ($assetQuery->includeSubfolders) {
            if ($queryFolder === null) {
                $queryFolder = Folders::getFolderById($assetQuery->folderId);
                if (! $queryFolder) {
                    throw new QueryAbortedException;
                }
            }
            $query
                ->where('volumeId', $queryFolder->volumeId)
                ->where('id', '!=', $queryFolder->id)
                ->where('path', 'like', "$queryFolder->path%");
        } else {
            $query->where('parentId', $assetQuery->folderId);
        }

        if ($assetQuery->search) {
            // `search` will already be normalized to a SearchQuery obj via _includeFoldersInIndexElements(),
            // and we already know it only has one token
            /** @var SearchQuery $searchQuery */
            $searchQuery = $assetQuery->search;
            $token = Arr::first($searchQuery->getTokens());
            self::_applyFolderQuerySearchCondition($query, $token);
        }

        return $query;
    }

    private static function _applyFolderQuerySearchCondition(Builder $query, SearchQueryTerm|SearchQueryTermGroup $token): void
    {
        if ($token instanceof SearchQueryTermGroup) {
            $query->where(function (Builder $q) use ($token) {
                foreach ($token->terms as $i => $term) {
                    if ($i === 0) {
                        self::_applyFolderQuerySearchCondition($q, $term);
                    } else {
                        $q->orWhere(function (Builder $sub) use ($term) {
                            self::_applyFolderQuerySearchCondition($sub, $term);
                        });
                    }
                }
            });

            return;
        }

        $isPgsql = DB::getDriverName() === 'pgsql';

        /** @var SearchQueryTerm $token */
        if ($token->subLeft || $token->subRight) {
            $pattern = sprintf(
                '%s%s%s',
                $token->subLeft ? '%' : '',
                $token->term,
                $token->subRight ? '%' : '',
            );
            $query->where('name', $isPgsql ? 'ilike' : 'like', $pattern);

            return;
        }

        // Only Postgres supports case-sensitive queries
        if ($isPgsql) {
            $query->whereRaw('lower("name") = ?', [mb_strtolower((string) $token->term)]);

            return;
        }

        $query->where('name', $token->term);
    }

    /**
     * Transforms an VolumeFolderModel into a source info array.
     */
    /** @return SourceInfo */
    private static function _assembleSourceInfoForFolder(VolumeFolder $folder, ?User $user = null): array
    {
        $volume = $folder->getVolume();
        if (! $folder->parentId) {
            $volumeHandle = $volume->handle ?? false;
        } else {
            $volumeHandle = false;
        }

        $canUpload = $user && Gate::check('uploadAsset', $folder);
        $canMoveTo = $user && Gate::check('moveIntoFolder', $folder);

        $sourcePathInfo = $folder->getSourcePathInfo();

        $source = [
            'key' => $folder->parentId ? "folder:$folder->uid" : "volume:$volume->uid",
            'label' => $folder->parentId ? $folder->name : t($folder->name, category: 'site'),
            'hasThumbs' => true,
            'criteria' => ['folderId' => $folder->id],
            'defaultSort' => ['dateCreated', 'desc'],
            'defaultSourcePath' => $sourcePathInfo ? [$sourcePathInfo] : null,
            'data' => [
                'volume-handle' => $volumeHandle,
                'folder-id' => $folder->id,
                'can-upload' => $folder->volumeId === null || $canUpload,
                'can-move-to' => $canMoveTo,
                'fs-type' => $volume->sourceFilesystemType(),
            ],
        ];

        if ($user && ! $user->can("viewPeerAssets:$volume->uid")) {
            $source['criteria']['uploaderId'] = $user->id;
        }

        return $source;
    }

    private static function isFolderIndex(): bool
    {
        return app(CurrentElementIndex::class)->isActive() && request()->boolean('foldersOnly');
    }

    /** @param array<string, bool|float|int|string|array<string, bool|float|int|string|null>|null> $values */
    #[Override]
    public function setAttributesFromRequest(array $values): void
    {
        // alt='' actually means something, so we should preserve it.
        if (Arr::has($values, 'alt')) {
            $this->alt = Arr::pull($values, 'alt') ?? '';
        }

        parent::setAttributesFromRequest($values);
    }

    /**
     * Returns the volume’s ID.
     */
    #[AllowedInSandbox]
    public function getVolumeId(): ?int
    {
        return (int) $this->_volumeId ?: null;
    }

    /**
     * Sets the volume’s ID.
     */
    public function setVolumeId(?int $id = null): void
    {
        if ($id !== $this->getVolumeId()) {
            $this->_volumeId = $id;
            $this->_volume = null;
        }
    }

    #[Override]
    protected function cacheTags(): array
    {
        $tags = [
            "volume:$this->_volumeId",
        ];

        // Did the volume just change?
        if ($this->_volumeId !== $this->_oldVolumeId) {
            $tags[] = "volume:$this->_oldVolumeId";
        }

        return $tags;
    }

    /** @return list<array{label?: string, href?: string, actions?: list<array{type: string, label: string, href: string, selected: bool}>}|null> */
    #[Override]
    protected function crumbs(): array
    {
        $volume = $this->getVolume();

        $crumbs = [
            [
                'label' => t('Assets'),
                'href' => Url::cpUrl('assets'),
            ],
        ];

        // Is the volume’s source enabled?
        if (ElementSources::sourceExists(Asset::class, "volume:$volume->uid")) {
            $volumes = Volumes::getViewableVolumes();

            // Filter out any volumes that don’t have an enabled source
            $sources = ElementSources::getSources(Asset::class);
            $sourceKeys = array_flip(array_filter(array_map(fn (array $source) => $source['key'] ?? null, $sources->all())));
            $volumes = $volumes->filter(fn (Volume $v) => isset($sourceKeys["volume:$v->uid"]));

            $volumeOptions = $volumes->map(fn (Volume $v) => [
                'type' => 'link',
                'label' => $v->getUiLabel(),
                'href' => Url::cpUrl("assets/$v->handle"),
                'selected' => $v->id === $volume->id,
            ]);

            $current = $volumeOptions->first(fn (array $o) => $o['selected'])
                ?? $volumeOptions->first();

            if ($volumeOptions->count() > 1) {
                // A crumb is shaped like a link action item, so the current
                // option doubles as the crumb and the whole set as its menu.
                $crumbs[] = [
                    'label' => $current['label'],
                    'href' => $current['href'],
                    'actions' => $volumeOptions->all(),
                ];
            } else {
                $crumbs[] = [
                    'label' => $current['label'],
                    'href' => $current['href'],
                ];
            }
        } else {
            // Just show its name w/o a link
            $crumbs[] = [
                'label' => $volume->getUiLabel(),
            ];
        }

        $uri = "assets/$volume->handle";

        if ($this->folderPath !== null) {
            $subfolders = Arr::whereNotEmpty(explode('/', $this->folderPath));
            foreach ($subfolders as $subfolder) {
                $uri .= "/$subfolder";
                $crumbs[] = [
                    'label' => $subfolder,
                    'href' => Url::cpUrl($uri),
                ];
            }
        }

        return $crumbs;
    }

    /**
     * ---
     * ```php
     * $url = $asset->cpEditUrl;
     * ```
     * ```twig{2}
     * {% if asset.isEditable %}
     *   <a href="{{ asset.cpEditUrl }}">Edit</a>
     * {% endif %}
     * ```
     */
    protected function cpEditUrl(): ?string
    {
        if ($this->isFolder) {
            return null;
        }

        $volume = $this->getVolume();
        if ($volume->isTemporary()) {
            return null;
        }

        $filename = preg_replace('/\s+/', '-', $this->getFilename(false));
        $path = "assets/edit/$this->id-$filename";

        return Url::cpUrl($path);
    }

    public function getPostEditUrl(): string
    {
        return Url::cpUrl('assets');
    }

    /**
     * The asset's own action menu items for the Inertia editor — the Form-system
     * counterpart to the items {@see safeActionMenuItems()} builds with inline
     * jQuery. Everything that opens a legacy modal (the file preview, the image
     * editor, the replace-file uploader) is described here and dispatched by the
     * client, which reloads afterwards so the file's details come back from the
     * server rather than being patched into the DOM.
     *
     * @return list<array<string, mixed>>
     */
    #[Override]
    protected function extraActionMenuDescriptors(): array
    {
        $user = currentUserElement();
        $items = [];

        if (AssetsService::getAssetPreviewHandler($this) !== null) {
            $items[] = [
                'label' => t('Preview file'),
                'icon' => 'view',
                'behavior' => [
                    'type' => 'previewFile',
                    'assetId' => $this->id,
                    'settings' => [
                        'startingWidth' => $this->width,
                        'startingHeight' => $this->height,
                    ],
                ],
            ];
        }

        $items[] = [
            'label' => t('Download'),
            'icon' => 'download',
            'behavior' => [
                'type' => 'download',
                'actionUrl' => Url::actionUrl('assets/download-asset'),
                'params' => ['assetId' => $this->id],
            ],
        ];

        if ($user && $this->volumeId && $this->canView($user)) {
            $items[] = [
                'label' => t('Show in folder'),
                'icon' => 'magnifying-glass',
                'behavior' => [
                    'type' => 'link',
                    'href' => Url::actionUrl('assets/show-in-folder', ['assetId' => $this->id]),
                ],
            ];
        }

        if ($user?->can('replaceFile', $this)) {
            $items[] = [
                'label' => t('Replace file'),
                'icon' => 'upload',
                'behavior' => [
                    'type' => 'replaceFile',
                    'assetId' => $this->id,
                    'fsType' => $this->getVolume()->sourceFilesystemType(),
                ],
            ];
        }

        if ($this->getSupportsImageEditor() && $user?->can('editImage', $this)) {
            $items[] = [
                'label' => t('Open in Image Editor'),
                'icon' => 'edit',
                'behavior' => [
                    'type' => 'editImage',
                    'assetId' => $this->id,
                ],
            ];
        }

        if ($user?->isAdmin() && Cms::config()->allowAdminChanges) {
            $items[] = [
                'label' => t('Volume settings'),
                'icon' => 'gear',
                'behavior' => [
                    'type' => 'slideout',
                    'action' => 'volumes/edit-volume',
                    'params' => ['volumeId' => $this->volumeId],
                ],
            ];

            $fsHandle = $this->getVolume()->getFsHandle();

            if (
                is_string($fsHandle) &&
                ! str_starts_with($fsHandle, Volume::STORAGE_DISK_PREFIX) &&
                Filesystems::getFilesystemByHandle($fsHandle)
            ) {
                $items[] = [
                    'label' => t('Filesystem settings'),
                    'icon' => 'gear',
                    'behavior' => [
                        'type' => 'slideout',
                        'url' => Url::cpUrl("settings/filesystems/$fsHandle/edit"),
                    ],
                ];
            }
        }

        return $items;
    }

    /** @return list<array<string, bool|int|string|MenuItemType|list<array<string, bool|int|string|MenuItemType>>>> */
    #[Override]
    protected function safeActionMenuItems(): array
    {
        $items = parent::safeActionMenuItems();

        $user = currentUserElement();
        $updatePreviewThumbJs = $this->_updatePreviewThumbJs();

        $viewItems = [];

        // Preview
        if (AssetsService::getAssetPreviewHandler($this) !== null) {
            $previewId = sprintf('action-preview-%s', mt_rand());
            $viewItems[] = [
                'type' => MenuItemType::Button,
                'id' => $previewId,
                'icon' => 'view',
                'label' => t('Preview file'),
            ];

            HtmlStack::jsWithVars(fn ($id, $assetId, $settings) => <<<JS
$('#' + $id).on('activate', () => {
  new Craft.PreviewFileModal($assetId, $settings)
});
JS, [
                InputNamespace::namespaceId($previewId),
                $this->id,
                [
                    'startingWidth' => $this->width,
                    'startingHeight' => $this->height,
                ],
            ]);
        }

        // Download
        $downloadId = sprintf('action-download-%s', mt_rand());
        $viewItems[] = [
            'type' => MenuItemType::Button,
            'id' => $downloadId,
            'icon' => 'download',
            'label' => t('Download'),
        ];

        HtmlStack::jsWithVars(fn ($id, $assetId) => <<<JS
$('#' + $id).on('activate', () => {
  const form = Craft.createForm().appendTo(Garnish.\$bod);
  form.append(Craft.getCsrfInput());
  $('<input/>', {type: 'hidden', name: 'action', value: 'assets/download-asset'}).appendTo(form);
  $('<input/>', {type: 'hidden', name: 'assetId', value: $assetId}).appendTo(form);
  $('<input/>', {type: 'submit', value: 'Submit'}).appendTo(form);
  form.submit();
  form.remove();
});
JS, [
            InputNamespace::namespaceId($downloadId),
            $this->id,
        ]);

        // Show in Folder
        if ($user && $this->volumeId && $this->canView($user)) {
            $viewItems[] = [
                'type' => MenuItemType::Link,
                'icon' => 'magnifying-glass',
                'label' => t('Show in folder'),
                'url' => Url::actionUrl('assets/show-in-folder', [
                    'assetId' => $this->id,
                ]),
            ];
        }

        $viewIndex = Collection::make($items)->search(fn (array $item) => str_starts_with($item['id'] ?? '', 'action-view-'));
        array_splice($items, $viewIndex !== false ? $viewIndex + 1 : 0, 0, $viewItems);

        $items[] = ['type' => MenuItemType::HR];

        // Replace file
        if (
            $user &&
            $user->can('replaceFile', $this)
        ) {
            $replaceId = sprintf('action-replace-%s', mt_rand());
            $items[] = [
                'type' => MenuItemType::Button,
                'id' => $replaceId,
                'icon' => 'upload',
                'label' => t('Replace file'),
                'showInChips' => false,
            ];

            HtmlStack::jsWithVars(fn ($id, $namespace, $assetId, $fsType, $dimensionsLabel) => <<<JS
$('#' + $id).on('activate', () => {
  const fileInput = $('<input/>', {type: 'file', name: 'replaceFile', class: 'replaceFile hidden'}).appendTo(Garnish.\$bod);
  const uploader = Craft.createUploader($fsType, fileInput, {
    dropZone: null,
    fileInput: fileInput,
    paramName: 'replaceFile',
    replace: true,
    events: {
      fileuploadstart: () => {
        $('#' + Craft.namespaceId('thumb-container', $namespace)).addClass('loading');
      },
      fileuploaddone: (event, data) => {
        const result = event instanceof CustomEvent ? event.detail : data.result;

        // Update the filename input and serialized param value
        const filenameInput = $('#' + Craft.namespaceId('new-filename', $namespace))
        const oldFilenameValue = encodeURIComponent(filenameInput.val());
        filenameInput.val(result.filename);

        let form = filenameInput.closest('form');

        // Make sure the form is for this asset
        let elementEditor = form.data('elementEditor');
        if (elementEditor?.settings.elementId !== $assetId) {
          form = null;
          elementEditor = null;
        }

        const initialSerializedData = form?.data('initialSerializedValue');
        if (initialSerializedData) {
          const inputName = encodeURIComponent(filenameInput.attr('name'));
          const newFilenameValue = encodeURIComponent(result.filename);
          form.data('initialSerializedValue', initialSerializedData
            .replace(inputName + '=' + oldFilenameValue, inputName + '=' + newFilenameValue));
        }

        // Update the file size value
        $('#' + Craft.namespaceId('file-size-value', $namespace))
          .text(result.formattedSize)
          .attr('title', result.formattedSizeInBytes);

        // Update the dimensions value
        let dimensionsVal = $('#' + Craft.namespaceId('dimensions-value', $namespace))
        if (result.dimensions) {
          if (!dimensionsVal.length) {
            $(
              '<div class="data">' +
              '<dt class="heading">' + $dimensionsLabel + '</div>' +
              '<dd id="dimensions-value" class="value"></div>' +
              '</div>'
            ).appendTo($('#' + Craft.namespaceId('details', $namespace) + ' > .meta.read-only'))
            dimensionsVal = $('#' + Craft.namespaceId('dimensions-value', $namespace))
          }
          dimensionsVal.text(result.dimensions);
        } else if (dimensionsVal.length) {
          dimensionsVal.parent().remove();
        }

        // Update the timestamp on the element editor
        if (elementEditor && result.updatedTimestamp) {
          elementEditor.settings.updatedTimestamp = result.updatedTimestamp;
          elementEditor.settings.canonicalUpdatedTimestamp = result.updatedTimestamp;
        }

        $updatePreviewThumbJs
        Craft.cp.runQueue();

        if (Craft.broadcaster) {
          Craft.broadcaster.postMessage({
            event: 'saveElement',
            id: $assetId,
          })
        }

        if (result.error) {
          $('#' + Craft.namespaceId('thumb-container', $namespace)).removeClass('loading');
          alert(result.error);
        } else {
          Craft.cp.displayNotice(Craft.t('app', 'New file uploaded.'));
          // update the View menu item link
          let viewBtn = $('#action-menu .menu-item[data-view]');
          if (viewBtn && result.resultingUrl) {
            viewBtn.attr('href', result.resultingUrl)
          }
        }
      },
      fileuploadfail: (event, data) => {
        const file = data.data.getAll('replaceFile');
        const backupFilename = file[0].name;

        const response = event instanceof Event
          ? event.detail
          : data?.jqXHR?.responseJSON;

        let {message, filename} = response || {};

        if (!message) {
          if (!filename) {
            filename = backupFilename;
          }
          message = filename
            ? Craft.t('app', 'Replace file failed for “{filename}”.', {filename})
            : Craft.t('app', 'Replace file failed.');
        }

        Craft.cp.displayError(message);
      },
      fileuploadalways: (event, data) => {
        $('#' + Craft.namespaceId('thumb-container', $namespace)).removeClass('loading');
      },
    }
  })

  uploader.setParams({
    assetId: $assetId,
  })

  fileInput.click();
});
JS, [
                InputNamespace::namespaceId($replaceId),
                InputNamespace::get(),
                $this->id,
                $this->getVolume()->sourceFilesystemType(),
                t('Dimensions'),
            ]);
        }

        // Image editor
        if (
            $this->getSupportsImageEditor() &&
            $user &&
            $user->can('editImage', $this)
        ) {
            $editImageId = sprintf('action-image-edit-%s', mt_rand());
            $items[] = [
                'type' => MenuItemType::Button,
                'id' => $editImageId,
                'icon' => 'edit',
                'label' => t('Open in Image Editor'),
            ];

            HtmlStack::jsWithVars(fn ($id, $assetId) => <<<JS
$('#' + $id).on('activate', () => {
  new Craft.AssetImageEditor($assetId, {
    allowDegreeFractions: Craft.isImagick,
    onSave: (data) => {
      if (!data.newAssetId) {
        $updatePreviewThumbJs
      }
    },
  })
});
JS, [
                InputNamespace::namespaceId($editImageId),
                $this->id,
            ]);
        }

        if (
            app(ElementRequest::class)->element() === $this &&
            currentUser()->isAdmin() &&
            Cms::config()->allowAdminChanges
        ) {
            $items[] = ['type' => MenuItemType::HR];

            // Volume settings
            $volumeEditId = sprintf('edit-volume-%s', mt_rand());
            $items[] = [
                'id' => $volumeEditId,
                'icon' => 'gear',
                'label' => t('Volume settings'),
            ];

            HtmlStack::jsWithVars(fn ($id, $params) => <<<JS
(() => {
  $('#' + $id).on('activate', function() {
    const params = $params;
    new Craft.CpScreenSlideout('volumes/edit-volume', {params});
  });
})();
JS, [
                InputNamespace::namespaceId($volumeEditId),
                ['volumeId' => $this->volumeId],
            ]);

            $fsHandle = $this->getVolume()->getFsHandle();
            if (is_string($fsHandle) && ! str_starts_with($fsHandle, Volume::STORAGE_DISK_PREFIX) && Filesystems::getFilesystemByHandle($fsHandle)) {
                $fsEditId = sprintf('edit-fs-%s', mt_rand());
                $items[] = [
                    'id' => $fsEditId,
                    'icon' => 'gear',
                    'label' => t('Filesystem settings'),
                ];

                HtmlStack::jsWithVars(fn ($id, $url) => <<<JS
(() => {
  $('#' + $id).on('activate', function() {
    new Craft.CpScreenSlideout($url);
  });
})();
JS, [
                    InputNamespace::namespaceId($fsEditId),
                    Url::cpUrl("settings/filesystems/$fsHandle/edit"),
                ]);
            }
        }

        return $items;
    }

    /**
     * Returns an `<img>` tag based on this asset.
     *
     * @param  ImageTransform|string|TransformConfig|null  $transform  The transform to use when generating the html.
     * @param  string[]|null  $sizes  The widths/x-descriptors that should be used for the `srcset` attribute
     *                                (see [[getSrcset()]] for example syntaxes)
     *
     * @throws InvalidArgumentException
     */
    #[AllowedInSandbox]
    public function getImg(mixed $transform = null, ?array $sizes = null): ?Markup
    {
        if ($transform === null) {
            if ($this->kind !== FileKind::Image->value) {
                return null;
            }

            $url = $this->getUrl();
            $width = $this->getWidth();
            $height = $this->getHeight();
        }

        if ($transform !== null) {
            $result = $this->_tryTransform($transform);

            if ($result === null) {
                return null;
            }

            $url = $result->url;
            $width = $result->width;
            $height = $result->height;
        }

        if ($url === null) {
            return null;
        }

        return Template::raw(Html::tag('img', '', [
            'src' => $url,
            'width' => $width,
            'height' => $height,
            'srcset' => $sizes ? $this->getSrcset($sizes, $transform) : false,
            'alt' => $this->thumbAlt(),
        ]));
    }

    /**
     * Returns a `srcset` attribute value based on the given widths or x-descriptors.
     *
     * For example, if you pass `['100w', '200w']`, you will get:
     *
     * ```
     * image-url@100w.ext 100w,
     * image-url@200w.ext 200w
     * ```
     *
     * If you pass x-descriptors, it will be assumed that the image’s current width is the `1x` width.
     * So if you pass `['1x', '2x']` on an image with a 100px-wide transform applied, you will get:
     *
     * ```
     * image-url@100w.ext,
     * image-url@200w.ext 2x
     * ```
     *
     * @param  string[]  $sizes
     * @param  ImageTransform|string|TransformConfig|null  $transform  A transform handle or configuration that should be applied to the image
     * @return string|false The `srcset` attribute value, or `false` if it can’t be determined
     *
     * @throws InvalidArgumentException
     */
    #[AllowedInSandbox]
    public function getSrcset(array $sizes, mixed $transform = null): string|false
    {
        $urls = array_filter($this->getUrlsBySize($sizes, $transform));

        if (empty($urls)) {
            return false;
        }

        $srcset = [];

        foreach ($urls as $size => $url) {
            if ($size === '1x') {
                $srcset[] = $url;
            } else {
                $srcset[] = "$url $size";
            }
        }

        return implode(', ', $srcset);
    }

    /**
     * Returns an array of image transform URLs based on the given widths or x-descriptors.
     *
     * For example, if you pass `['100w', '200w']`, you will get:
     *
     * ```php
     * [
     *     '100w' => 'image-url@100w.ext',
     *     '200w' => 'image-url@200w.ext'
     * ]
     * ```
     *
     * If you pass x-descriptors, it will be assumed that the image’s current width is the indented 1x width.
     * So if you pass `['1x', '2x']` on an image with a 100px-wide transform applied, you will get:
     *
     * ```php
     * [
     *     '1x' => 'image-url@100w.ext',
     *     '2x' => 'image-url@200w.ext'
     * ]
     * ```
     *
     * @param  string[]  $sizes
     * @param  ImageTransform|string|TransformConfig|null  $transform  A transform handle or configuration that should be applied to the image
     * @return array<string, string|null>
     */
    #[AllowedInSandbox]
    public function getUrlsBySize(array $sizes, mixed $transform = null): array
    {
        $urls = [];
        $result = $transform !== null ? $this->_tryTransform($transform) : null;

        if ($transform !== null) {
            if ($result === null) {
                return [];
            }
        }

        [$currentWidth, $currentHeight] = $result
            ? [$result->width, $result->height]
            : $this->_dimensions();

        foreach ($sizes as $size) {
            if ($size === '1x') {
                $urls[$size] = $result !== null ? $result->url : $this->getUrl();

                continue;
            }

            [$value, $unit] = AssetsHelper::parseSrcsetSize($size);

            $sizeTransform = is_array($transform)
                ? $transform
                : ($transform !== null ? ['transform' => $transform] : []);

            if ($unit === 'w') {
                $sizeTransform['width'] = (int) $value;
            } else {
                if (! $currentWidth) {
                    continue;
                }

                $sizeTransform['width'] = (int) ceil($currentWidth * $value);
            }

            // Only set the height if the current transform has a height set on it
            if ($this->_transformHasHeight($transform) && $currentWidth && $currentHeight) {
                if ($unit === 'w') {
                    $sizeTransform['height'] = (int) ceil($currentHeight * $sizeTransform['width'] / $currentWidth);
                } else {
                    $sizeTransform['height'] = (int) ceil($currentHeight * $value);
                }
            }

            $urls["$value$unit"] = $this->_tryTransform($sizeTransform)?->url;
        }

        return $urls;
    }

    #[Override]
    public function getIsTitleTranslatable(): bool
    {
        return $this->getVolume()->titleTranslationMethod !== TranslationMethod::None;
    }

    #[Override]
    public function getTitleTranslationDescription(): ?string
    {
        return $this->getVolume()->titleTranslationMethod->description();
    }

    #[Override]
    public function getTitleTranslationKey(): string
    {
        $volume = $this->getVolume();

        return $volume->titleTranslationMethod->elementKey($this, $volume->titleTranslationKeyFormat);
    }

    /**
     * Returns the Alternative Text field’s translation key.
     */
    public function getAltTranslationKey(): string
    {
        $volume = $this->getVolume();

        return $volume->altTranslationMethod->elementKey($this, $volume->altTranslationKeyFormat);
    }

    #[Override]
    public function getFieldLayout(): ?FieldLayout
    {
        try {
            return $this->getVolume()->getFieldLayout();
        } catch (RuntimeException) {
            return null;
        }
    }

    /**
     * Returns the asset’s volume folder.
     *
     * @throws RuntimeException if [[folderId]] is missing or invalid
     */
    #[AllowedInSandbox]
    public function getFolder(): VolumeFolder
    {
        if (! isset($this->folderId)) {
            throw new RuntimeException('Asset is missing its folder ID');
        }

        if (($folder = Folders::getFolderById($this->folderId)) === null) {
            throw new RuntimeException('Invalid folder ID: '.$this->folderId);
        }

        return $folder;
    }

    /**
     * Returns the asset’s volume.
     *
     * @throws RuntimeException if [[volumeId]] is missing or invalid
     */
    #[AllowedInSandbox]
    public function getVolume(): Volume
    {
        if (isset($this->_volume)) {
            return $this->_volume;
        }

        if (! isset($this->_volumeId)) {
            return Volumes::getTemporaryVolume();
        }

        if (($volume = Volumes::getVolumeById($this->_volumeId)) === null) {
            throw new RuntimeException('Invalid volume ID: '.$this->_volumeId);
        }

        return $this->_volume = $volume;
    }

    /**
     * Returns the user that uploaded the asset, if known.
     */
    #[AllowedInSandbox]
    public function getUploader(): ?User
    {
        if (isset($this->_uploader)) {
            return $this->_uploader;
        }

        if (! isset($this->uploaderId)) {
            return null;
        }

        if (($this->_uploader = Users::getUserById($this->uploaderId)) === null) {
            // The uploader is probably soft-deleted. Just pretend no uploader is set
            return null;
        }

        return $this->_uploader;
    }

    /**
     * Sets the asset's uploader.
     */
    public function setUploader(?User $uploader = null): void
    {
        $this->_uploader = $uploader;
    }

    #[AllowedInSandbox]
    public function transform(
        #[\SensitiveParameter] mixed $definition,
        ?bool $immediately = null,
        ?string $transformer = null,
    ): AssetTransformResult {
        return app(AssetTransformers::class)->transform($this, $definition, $immediately, $transformer);
    }

    protected function _tryTransform(
        #[\SensitiveParameter] mixed $definition,
        ?bool $immediately = null,
    ): ?AssetTransformResult {
        try {
            return $this->transform($definition, $immediately);
        } catch (AssetTransformException|NotSupportedException $exception) {
            report($exception);

            return null;
        }
    }

    private function _transformHasHeight(mixed $transform): bool
    {
        if (is_array($transform)) {
            if (array_key_exists('height', $transform)) {
                return $transform['height'] !== null;
            }

            if (! array_key_exists('transform', $transform)) {
                return false;
            }

            return $this->_transformHasHeight($transform['transform']);
        }

        return ImageTransformHelper::normalizeTransform($transform)?->height !== null;
    }

    /**
     * Returns the element’s full URL.
     *
     * @param  ImageTransform|string|TransformConfig|null  $transform  Deprecated. Use {@see transform()} and read the result’s URL instead.
     * @param  bool|null  $immediately  Deprecated. Whether the image should be transformed immediately.
     *
     * @throws RuntimeException
     */
    #[Override]
    public function getUrl(mixed $transform = null, ?bool $immediately = null): ?string
    {
        if ($transform !== null || $immediately !== null) {
            Deprecator::log(
                'Asset::getUrl($transform)',
                'Passing transform arguments to `Asset::getUrl()` is deprecated. Use `Asset::transform()->url` instead.',
            );
        }

        if ($this->isFolder) {
            return null;
        }

        event($event = new AssetUrlResolving($this, $transform));

        $url = $event->url;

        // If AssetUrlResolving::$url is set to null, only respect that if $handled is true
        if ($event->url === null && ! $event->handled) {
            $url = $this->_url($transform, $immediately);
        }

        event($event = new AssetUrlDefined($this, $transform, $url));

        // If AssetUrlDefined::$url is set to null, only respect that if $handled is true
        if ($event->url !== null || $event->handled) {
            $url = $event->url;
        }

        return $url !== null ? Html::encodeSpaces($url) : $url;
    }

    private function _url(mixed $transform = null, ?bool $immediately = null): ?string
    {
        if ($transform !== null) {
            return $this->_tryTransform($transform, $immediately)?->url;
        }

        if (! $this->folderId) {
            return null;
        }

        $volume = $this->getVolume();

        if (! $volume->sourceHasUrls() || $volume->isTemporary()) {
            return null;
        }

        return Html::encodeSpaces(AssetsHelper::generateUrl($this));
    }

    protected function thumbUrl(int $size): ?string
    {
        if ($this->isFolder) {
            return null;
        }

        $forCard = $size % 128 === 0;

        if (! $forCard && $this->getWidth() && $this->getHeight()) {
            [$width, $height] = AssetsHelper::scaledDimensions((int) $this->getWidth(), (int) $this->getHeight(), $size, $size);
        } else {
            $width = $height = $size;
        }

        return AssetsService::getThumbUrl($this, $width, $height, false);
    }

    protected function thumbSvg(): string
    {
        if ($this->isFolder) {
            return file_get_contents(Aliases::get('@resources/public/images/thumbs/folder.svg'));
        }

        return AssetsHelper::iconSvg($this->getExtension());
    }

    protected function thumbAlt(): ?string
    {
        if ($this->isFolder) {
            return null;
        }

        $extension = $this->getExtension();
        if (! ImageHelper::canManipulateAsImage($extension)) {
            return $extension;
        }

        return $this->alt;
    }

    #[Override]
    protected function hasCheckeredThumb(): bool
    {
        if ($this->isFolder) {
            return false;
        }

        return in_array(strtolower($this->getExtension()), ['png', 'gif', 'svg'], true);
    }

    /**
     * Returns preview thumb image HTML.
     */
    public function getPreviewThumbImg(int $desiredWidth, int $desiredHeight): string
    {
        $srcsets = [];
        [$width, $height] = AssetsHelper::scaledDimensions((int) $this->getWidth(), (int) $this->getHeight(), $desiredWidth, $desiredHeight);
        $thumbSizes = [
            [$width, $height],
            [$width * 2, $height * 2],
        ];
        foreach ($thumbSizes as [$width, $height]) {
            $url = AssetsService::getThumbUrl($this, $width, $height);
            $srcsets[] = sprintf('%s %sw', $url, $width);
        }

        return Html::tag('img', '', [
            'sizes' => "{$thumbSizes[0][0]}px",
            'srcset' => implode(', ', $srcsets),
            'alt' => $this->thumbAlt(),
            'data' => [
                'animated' => $this->couldHaveAnimatedThumb(),
            ],
        ]);
    }

    /** @return list<array{label: string, url: string}> */
    #[Override]
    public function getPreviewTargets(): array
    {
        return [];
    }

    /**
     * Returns the filename, with or without the extension.
     *
     * @throws RuntimeException if the filename isn’t set yet
     */
    #[AllowedInSandbox]
    public function getFilename(bool $withExtension = true): string
    {
        if ($this->isFolder) {
            return '';
        }

        if (! isset($this->_filename)) {
            throw new RuntimeException('Asset not configured with its filename');
        }

        if ($withExtension) {
            return $this->_filename;
        }

        return pathinfo($this->_filename, PATHINFO_FILENAME);
    }

    /**
     * Sets the filename (with extension).
     */
    public function setFilename(string $filename): void
    {
        $this->_filename = $filename;
    }

    /**
     * Returns the file extension.
     */
    #[AllowedInSandbox]
    public function getExtension(): string
    {
        return pathinfo($this->_filename, PATHINFO_EXTENSION);
    }

    #[Override]
    protected function couldHaveAnimatedThumb(): bool
    {
        if ($this->getExtension() === 'gif') {
            return true;
        }

        return $this->getExtension() === 'webp';
    }

    /**
     * Returns the file’s MIME type based, if it can be determined.
     *
     * If a transform is applied via the `$transform` argument, the MIME type will be based on the transform’s format.
     *
     * @param  ImageTransform|string|TransformConfig|null  $transform  A transform handle or configuration that should be applied to the mime type
     *
     * @throws ImageTransformException if $transform is an invalid transform handle
     */
    #[AllowedInSandbox]
    public function getMimeType(mixed $transform = null): ?string
    {
        if ($transform !== null) {
            return $this->transform($transform)->mimeType;
        }

        return $this->_mimeType ?? File::getMimeTypeByExtension($this->_filename);
    }

    /**
     * Sets the file’s MIME type.
     */
    public function setMimeType(?string $mimeType): void
    {
        $this->_mimeType = $mimeType;
    }

    /**
     * Returns the file's format, if it can be determined.
     *
     * @param  ImageTransform|string|TransformConfig|null  $transform  A transform handle or configuration that should be applied to the image
     * @return string The asset's format
     *
     * @throws ImageTransformException If an invalid transform handle is supplied
     */
    #[AllowedInSandbox]
    public function getFormat(mixed $transform = null): string
    {
        if ($transform !== null) {
            return File::getExtensionByMimeType($this->transform($transform)->mimeType);
        }

        return $this->getExtension();
    }

    /**
     * Returns the image height.
     *
     * @param  ImageTransform|string|TransformConfig|null  $transform  A transform handle or configuration that should be applied to the image
     */
    #[AllowedInSandbox]
    public function getHeight(mixed $transform = null): ?int
    {
        if ($transform !== null) {
            return $this->transform($transform)->height;
        }

        return $this->_dimensions($transform)[1];
    }

    /**
     * Sets the image height.
     *
     * @param  int|null  $height  the image height
     */
    public function setHeight(?int $height): void
    {
        $this->_height = $height;
    }

    /**
     * Returns the image width.
     *
     * @param  TransformConfig|string|ImageTransform|null  $transform  A transform handle or configuration that should be applied to the image
     */
    #[AllowedInSandbox]
    public function getWidth(array|string|ImageTransform|null $transform = null): ?int
    {
        if ($transform !== null) {
            return $this->transform($transform)->width;
        }

        return $this->_dimensions($transform)[0];
    }

    /**
     * Sets the image width.
     *
     * @param  int|null  $width  the image width
     */
    public function setWidth(?int $width): void
    {
        $this->_width = $width;
    }

    /**
     * Returns the formatted file size, if known.
     *
     * @param  int|null  $decimals  the number of digits after the decimal point
     * @param  bool  $short  whether the size should be returned in short form (“kB” instead of “kilobytes”)
     */
    #[AllowedInSandbox]
    public function getFormattedSize(?int $decimals = null, bool $short = true): ?string
    {
        if (! isset($this->size)) {
            return null;
        }

        if ($short) {
            return I18N::getFormatter()->asShortSize($this->size, $decimals);
        }

        return I18N::getFormatter()->asSize($this->size, $decimals);
    }

    /**
     * Returns the formatted file size in bytes, if known.
     *
     * @param  bool  $short  whether the size should be returned in short form (“B” instead of “bytes”)
     */
    #[AllowedInSandbox]
    public function getFormattedSizeInBytes(bool $short = true): ?string
    {
        if (! isset($this->size)) {
            return null;
        }
        $params = [
            'n' => $this->size,
            'nFormatted' => I18N::getFormatter()->asDecimal($this->size),
        ];
        if ($short) {
            return t('{nFormatted} B', $params);
        }

        return t('{nFormatted} {n, plural, =1{byte} other{bytes}}', $params);
    }

    /**
     * Returns the image dimensions.
     */
    #[AllowedInSandbox]
    public function getDimensions(): ?string
    {
        $width = $this->getWidth();
        $height = $this->getHeight();
        if (! $width || ! $height) {
            return null;
        }

        return $width.'×'.$height;
    }

    /**
     * Returns the asset's path in the volume.
     *
     * @param  string|null  $filename  Filename to use. If not specified, the asset's filename will be used.
     */
    #[AllowedInSandbox]
    public function getPath(?string $filename = null): string
    {
        return $this->folderPath.($filename ?: $this->_filename);
    }

    /**
     * Return the path where the source for this Asset's transforms should be.
     */
    public function getImageTransformSourcePath(): string
    {
        $volume = $this->getVolume();

        if ($volume->sourceDisk() instanceof LocalFilesystemAdapter) {
            return File::normalizePath($volume->sourceDisk()->path($this->getPath()));
        }

        return Path::assetSources($this->id.'.'.$this->getExtension());
    }

    /**
     * Get a temporary copy of the actual file.
     *
     * @throws VolumeException If unable to fetch file from volume.
     * @throws RuntimeException If no volume can be found.
     */
    public function getCopyOfFile(): string
    {
        $tempFilename = File::uniqueName($this->_filename);
        $tempPath = Path::temp($tempFilename);
        AssetsHelper::downloadFile($this->getVolume()->sourceDisk(), $this->getPath(), $tempPath);

        return $tempPath;
    }

    /**
     * Returns a stream of the actual file.
     *
     * @return resource
     *
     * @throws RuntimeException if [[volumeId]] is missing or invalid
     * @throws FilesystemException if a stream cannot be created
     */
    #[AllowedInSandbox]
    public function getStream()
    {
        $stream = $this->getVolume()->sourceDisk()->readStream($this->getPath());

        if (! is_resource($stream)) {
            throw new FilesystemException("Unable to open {$this->getPath()}.");
        }

        return $stream;
    }

    /**
     * Returns the file’s contents.
     *
     * @throws RuntimeException if [[volumeId]] is missing or invalid
     * @throws AssetException if a stream could not be created
     */
    #[AllowedInSandbox]
    public function getContents(): string
    {
        return stream_get_contents($this->getStream());
    }

    /**
     * Generates a base64-encoded [data URL](https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/Data_URIs) for the asset.
     *
     * @throws RuntimeException if [[volumeId]] is missing or invalid
     * @throws AssetException if a stream could not be created
     */
    #[AllowedInSandbox]
    public function getDataUrl(): string
    {
        return Html::dataUrlFromString($this->getContents(), $this->getMimeType());
    }

    /**
     * Returns whether this asset can be edited by the image editor.
     */
    public function getSupportsImageEditor(): bool
    {
        $ext = $this->getExtension();

        return strcasecmp($ext, 'svg') !== 0 && ImageHelper::canManipulateAsImage($ext);
    }

    /**
     * Returns whether a user-defined focal point is set on the asset.
     */
    #[AllowedInSandbox]
    public function getHasFocalPoint(): bool
    {
        return isset($this->_focalPoint);
    }

    /**
     * Returns the focal point represented as an array with `x` and `y` keys, or null if it’s not an image.
     *
     * @param  bool  $asCss  whether the value should be returned in CSS syntax ("50% 25%") instead
     */
    /** @return array{x: float, y: float}|string|null */
    #[AllowedInSandbox]
    public function getFocalPoint(bool $asCss = false): array|string|null
    {
        if (! in_array($this->kind, [FileKind::Image->value, FileKind::Video->value], true)) {
            return null;
        }

        $focal = $this->_focalPoint ?? ['x' => 0.5, 'y' => 0.5];

        if ($asCss) {
            return ($focal['x'] * 100).'% '.($focal['y'] * 100).'%';
        }

        return $focal;
    }

    /**
     * Sets the asset's focal point.
     *
     * @throws InvalidArgumentException if $value is invalid
     */
    /** @param array{x: float|int|string, y: float|int|string}|string|null $value */
    public function setFocalPoint(array|string|null $value): void
    {
        if (is_array($value)) {
            if (! isset($value['x'], $value['y'])) {
                throw new InvalidArgumentException('$value should be a string or array with \'x\' and \'y\' keys.');
            }
            $value = [
                'x' => (float) $value['x'],
                'y' => (float) $value['y'],
            ];
        } elseif ($value !== null) {
            $focal = explode(';', $value);
            if (count($focal) !== 2) {
                throw new InvalidArgumentException('$value should be a string or array with \'x\' and \'y\' keys.');
            }
            $value = [
                'x' => (float) $focal[0],
                'y' => (float) $focal[1],
            ];
        }

        if ($value !== null && (
            $value['x'] < 0 ||
            $value['x'] > 1 ||
            $value['y'] < 0 ||
            $value['y'] > 1
        )) {
            $value = null;
        }

        $this->_focalPoint = $value;
    }

    // Indexes, etc.
    // -------------------------------------------------------------------------

    #[Override]
    public function getAttributeHtml(string $attribute): string|Stringable
    {
        if ($this->isFolder) {
            return '';
        }

        return parent::getAttributeHtml($attribute);
    }

    #[Override]
    protected function attributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'uploader':
                $uploader = $this->getUploader();

                return $uploader ? app(ElementHtml::class)->elementChipHtml($uploader) : '';

            case 'filename':
                return Html::tag('span', Html::encode($this->_filename), [
                    'class' => 'break-word',
                ]);

            case 'kind':
                return AssetsHelper::getFileKindLabel($this->kind);

            case 'size':
                if (! isset($this->size)) {
                    return '';
                }

                return Html::tag('span', $this->getFormattedSize(0), [
                    'title' => $this->getFormattedSizeInBytes(false),
                ]);

            case 'imageSize':
                return $this->getDimensions() ?? '';

            case 'width':
            case 'height':
                $size = $this->$attribute;

                return $size ? $size.'px' : '';

            case 'location':
                return $this->locationHtml();
        }

        return parent::attributeHtml($attribute);
    }

    #[Override]
    public function getInlineAttributeInputHtml(string $attribute): string
    {
        if ($this->isFolder) {
            return '';
        }

        return parent::getInlineAttributeInputHtml($attribute);
    }

    #[Override]
    protected function inlineAttributeInputHtml(string $attribute): string
    {
        return match ($attribute) {
            'alt' => FormFields::textareaHtml([
                'name' => 'alt',
                'value' => $this->alt,
            ]),
            default => parent::inlineAttributeInputHtml($attribute),
        };
    }

    /**
     * Returns the HTML for asset previews.
     *
     * @throws RuntimeException
     */
    public function getPreviewHtml(): string
    {
        $html = '';

        // See if we can show a thumbnail
        try {
            // Is the image editable, and is the user allowed to edit?
            $user = currentUser();
            $previewable = AssetsService::getAssetPreviewHandler($this) !== null;
            $editable = (
                $this->getSupportsImageEditor() &&
                $user?->can('editImage', $this)
            );

            $previewInner = match ($this->kind) {
                FileKind::Video->value => Html::tag('video', Html::tag('source', '', [
                    'type' => $this->getMimeType(),
                    'src' => $this->url,
                ]), [
                    'class' => 'preview-thumb',
                    'controls' => true,
                    'preload' => 'metadata',
                ]),
                FileKind::Audio->value => Html::tag('audio', Html::tag('source', '', [
                    'src' => $this->url,
                    'type' => $this->getMimeType(),
                ]), [
                    'controls' => true,
                    'preload' => 'metadata',
                ]),
                default => Html::tag('div', $this->getPreviewThumbImg(350, 190), [
                    'class' => 'preview-thumb',
                ]),
            };

            $previewThumbHtml =
                Html::beginTag('div', [
                    'id' => 'thumb-container',
                    'class' => array_filter([
                        'preview-thumb-container',
                        'button-fade',
                        $this->hasCheckeredThumb() ? 'checkered' : null,
                    ]),
                ]).
                $previewInner.
                Html::endTag('div'); // .preview-thumb-container

            if ($previewable || $editable) {
                $isMobile = request()->isMobileBrowser(true);
                $imageButtonHtml = Html::beginTag('div', [
                    'class' => array_filter([
                        'image-actions',
                        'buttons',
                        ($isMobile ? 'is-mobile' : null),
                    ]),
                ]);

                if ($previewable) {
                    $imageButtonHtml .= Html::button(t('Preview'), [
                        'id' => 'preview-btn',
                        'class' => ['btn', 'preview-btn'],
                        'aria-label' => t('Preview'),
                    ]);

                    $previewBtnId = InputNamespace::namespaceId('preview-btn');
                    $settings = [];
                    $width = $this->getWidth();
                    $height = $this->getHeight();
                    if ($width && $height) {
                        $settings['startingWidth'] = $width;
                        $settings['startingHeight'] = $height;
                    }
                    $jsSettings = Json::encode($settings);
                    $js = <<<JS
$('#$previewBtnId').on('activate', () => {
    new Craft.PreviewFileModal($this->id, null, $jsSettings)
});
JS;
                    HtmlStack::js($js);
                }

                if ($editable) {
                    $imageButtonHtml .= Html::button(t('Edit Image'), [
                        'id' => 'edit-btn',
                        'class' => ['btn', 'edit-btn'],
                    ]);

                    $editBtnId = InputNamespace::namespaceId('edit-btn');
                    $updatePreviewThumbJs = $this->_updatePreviewThumbJs();
                    $js = <<<JS
$('#$editBtnId').on('activate', () => {
    new Craft.AssetImageEditor($this->id, {
        allowDegreeFractions: Craft.isImagick,
        onSave: data => {
            if (data.newAssetId) {
                // If this is within an Assets field’s editor slideout, replace the selected asset
                const slideout = $('#$editBtnId').closest('[data-slideout]').data('slideout');
                if (slideout && slideout.settings.elementSelectInput) {
                    slideout.settings.elementSelectInput.replaceElement(slideout.\$element.data('id'), data.newAssetId)
                        .catch(() => {});
                }
                return;
            }

            $updatePreviewThumbJs
        },
    })
});
JS;
                    HtmlStack::js($js);
                }

                $imageButtonHtml .= Html::endTag('div'); // .image-actions

                if (request()->isMobileBrowser(true)) {
                    $previewThumbHtml .= $imageButtonHtml;
                } else {
                    $previewThumbHtml = Html::appendToTag($previewThumbHtml, $imageButtonHtml);
                }
            }

            $html .= $previewThumbHtml;
        } catch (RuntimeException) {
            // NBD
        }

        return $html;
    }

    private function _updatePreviewThumbJs(): string
    {
        $thumbContainerId = InputNamespace::namespaceId('thumb-container');

        return <<<JS
$('#$thumbContainerId')
    .addClass('loading')
    .append($('<div class="spinner spinner-absolute"/>'));
Craft.sendActionRequest('POST', 'assets/preview-thumb', {
    data: {
        assetId: $this->id,
        width: 350,
        height: 190,
    },
}).then(({data}) => {
    $('#$thumbContainerId').find('img').replaceWith(data.img);
}).finally(() => {
    $('#$thumbContainerId').removeClass('loading')
        .find('.spinner').remove();
});
JS;
    }

    #[Override]
    public function getSidebarHtml(bool $static): string
    {
        return implode("\n", [
            // Omit preview button on sidebar of slideouts
            $this->getPreviewHtml(),
            parent::getSidebarHtml($static),
        ]);
    }

    #[Override]
    protected function metaFieldsHtml(bool $static): string
    {
        return implode("\n", [
            FormFields::textFieldHtml([
                'label' => t('Filename'),
                'attribute' => 'newLocation',
                'id' => 'new-filename',
                'name' => 'newFilename',
                'value' => $this->_filename,
                'errors' => $this->errors()->get('newLocation'),
                'first' => true,
                'required' => true,
                'class' => ['text', 'filename'],
                'disabled' => $static,
            ]),
            parent::metaFieldsHtml($static),
        ]);
    }

    /** @return list<Node> */
    #[Override]
    protected function metaFieldsNodes(bool $static): array
    {
        return [
            Field::make(t('Filename'))
                ->required()
                ->control(
                    Text::make('newFilename')
                        ->value($this->_filename)
                        ->mode($static ? ControlMode::Disabled : ControlMode::Editable),
                ),
            ...parent::metaFieldsNodes($static),
        ];
    }

    /**
     * Renaming validates the folder path and the filename together as
     * `newLocation`, but the field that produced the error posts `newFilename`,
     * so its messages move to the name the Control answers to. They're moved
     * rather than copied so the error summary doesn't list each one twice.
     *
     * @return array<string, list<string>>
     */
    #[Override]
    public function formErrors(): array
    {
        $errors = parent::formErrors();

        if (isset($errors['newLocation'])) {
            $errors['newFilename'] = [
                ...($errors['newFilename'] ?? []),
                ...$errors['newLocation'],
            ];

            unset($errors['newLocation']);
        }

        return $errors;
    }

    /** @return array<string, Closure(): bool|string> */
    #[Override]
    protected function metadata(): array
    {
        return [
            t('Location') => $this->locationHtml(...),
            t('File size') => function () {
                $size = $this->getFormattedSize(0);
                if (! $size) {
                    return false;
                }
                $inBytes = $this->getFormattedSizeInBytes(false);

                return Html::tag('div', $size, [
                    'id' => 'file-size-value',
                    'title' => $inBytes,
                ]);
            },
            t('Uploaded by') => function () {
                $uploader = $this->getUploader();

                return $uploader ? app(ElementHtml::class)->elementChipHtml($uploader) : false;
            },
            t('Dimensions') => function () {
                $dimensions = $this->getDimensions();
                if (! $dimensions) {
                    return false;
                }

                return Html::tag('div', $dimensions, [
                    'id' => 'dimensions-value',
                ]);
            },
        ];
    }

    private function locationHtml(): string
    {
        $volume = $this->getVolume();
        $isTemp = $volume->isTemporary();

        if (! $isTemp) {
            $uri = "assets/$volume->handle";
            $items = [
                Html::a(t(Html::encode($volume->name), category: 'site'), Url::cpUrl($uri)),
            ];
        } else {
            $items = [
                Html::tag('span', t(Html::encode($volume->name), category: 'site')),
            ];
        }
        if ($this->folderPath) {
            $subfolders = Arr::whereNotEmpty(explode('/', $this->folderPath));
            foreach ($subfolders as $subfolder) {
                if (! $isTemp) {
                    $uri .= "/$subfolder";
                    $items[] = Html::a($subfolder, Url::cpUrl($uri));
                } else {
                    $items[] = Html::tag('span', $subfolder);
                }
            }
        }

        $items = array_map(fn ($item) => Html::li($item)->encode(false), $items);

        return Html::ul()->items(...$items)->class('path')->render();
    }

    #[Override]
    public function getGqlTypeName(): string
    {
        return self::gqlTypeName($this->getVolume());
    }

    /** @return list<string> */
    #[Override]
    public function attributes(): array
    {
        $names = array_flip(parent::attributes());

        unset(
            $names['avoidFilenameConflicts'],
            $names['keepFileOnDelete'],
            $names['sanitizeOnUpload'],
        );

        $names['extension'] = true;
        $names['filename'] = true;
        $names['focalPoint'] = true;
        $names['hasFocalPoint'] = true;
        $names['height'] = true;
        $names['mimeType'] = true;
        $names['path'] = true;
        $names['volumeId'] = true;
        $names['width'] = true;

        return array_keys($names);
    }

    #[Override]
    public function extraFields(): array
    {
        $names = parent::extraFields();
        $names[] = 'folder';
        $names[] = 'volume';

        return $names;
    }

    // Events
    // -------------------------------------------------------------------------

    #[Override]
    public function beforeSave(bool $isNew): bool
    {
        if (! isset($this->_filename)) {
            if (isset($this->newLocation)) {
                [, $this->filename] = AssetsHelper::parseFileLocation($this->newLocation);
            } elseif (isset($this->newFilename)) {
                $this->filename = $this->newFilename;
                $this->newFilename = null;
            }
        }

        if ($this->newFilename === '' || $this->newFilename === $this->getFilename()) {
            $this->newFilename = null;
        }

        // newFolderId/newFilename => newLocation
        if (isset($this->newFolderId) || isset($this->newFilename)) {
            $folderId = $this->newFolderId ?: $this->folderId;
            $filename = $this->newFilename ?? $this->_filename;
            $this->newLocation = "{folder:$folderId}$filename";
            $this->newFolderId = $this->newFilename = null;
        }

        // Get the (new?) folder ID
        if (isset($this->newLocation)) {
            [$folderId] = AssetsHelper::parseFileLocation($this->newLocation);
        } else {
            $folderId = $this->folderId;
        }

        // Fire a 'beforeHandleFile' event if we're going to be doing any file operations in afterSave()
        if (isset($this->newLocation) || isset($this->tempFilePath)) {
            event(new AssetFileHandling($this, isNew: ! $this->id));
        }

        // Set the kind based on filename
        $this->_setKind();

        // Give it a default title based on the file name, if it doesn't have a title yet
        if (! $this->id && ! $this->title) {
            $this->title = AssetsHelper::filename2Title(pathinfo($this->_filename, PATHINFO_FILENAME));
        }

        // Set the field layout
        $volume = Folders::getFolderById($folderId)->getVolume();

        if (! $volume->isTemporary()) {
            $this->fieldLayoutId = $volume->fieldLayoutId;
        }

        return parent::beforeSave($isNew);
    }

    /**
     * Sets the asset’s kind based on its filename.
     */
    private function _setKind(): void
    {
        if (isset($this->_filename)) {
            $this->kind = AssetsHelper::getFileKindByExtension($this->_filename);
        }
    }

    /**
     * @throws RuntimeException
     */
    #[Override]
    public function afterSave(bool $isNew): void
    {
        if (! $this->propagating) {
            $isImage = isset($this->tempFilePath) && AssetsHelper::getFileKindByExtension($this->tempFilePath) === FileKind::Image->value;

            $fallbackWidth = null;
            $fallbackHeight = null;

            if ($isImage) {
                // Auto-populate alt text from IPTC/XMP metadata on upload, before any cleaning strips it
                if (
                    ($this->alt === null || $this->alt === '') &&
                    $this->ruleset->inScenarios(AssetRules::SCENARIO_CREATE, AssetRules::SCENARIO_REPLACE)
                ) {
                    $alt = $this->_getAltFromXmpMetadata($this->tempFilePath) ?? $this->_getAltFromIptcMetadata($this->tempFilePath);
                    if ($alt !== null) {
                        // ensure it's UTF-8
                        // (see https://github.com/craftcms/cms/issues/19069)
                        $this->alt = Str::convertToUtf8($alt);
                    }
                }

                // Are we uploading an image that needs to be sanitized?
                if (
                    $this->ruleset->inScenarios(AssetRules::SCENARIO_REPLACE, AssetRules::SCENARIO_CREATE) &&
                    ($this->sanitizeOnUpload ?? (
                        ! request()->isCpRequest() ||
                        Cms::config()->sanitizeCpImageUploads
                    ))
                ) {
                    ImageHelper::cleanImageByPath($this->tempFilePath);
                }

                // if we're creating or replacing and image, get the width or height via getimagesize
                // in case loadImage is not able to get them properly (e.g. imagick runs out of memory)
                if ($this->ruleset->inScenarios(AssetRules::SCENARIO_REPLACE, AssetRules::SCENARIO_CREATE)) {
                    $imageSize = getimagesize($this->tempFilePath);
                    if (isset($imageSize[0])) {
                        $fallbackWidth = $imageSize[0];
                    }
                    if (isset($imageSize[1])) {
                        $fallbackHeight = $imageSize[1];
                    }
                }
            }

            // Relocate the file?
            if (isset($this->newLocation) || isset($this->tempFilePath)) {
                $this->_relocateFile();
            }

            // Get the asset record
            if (! $isNew) {
                $model = AssetModel::findOrFail($this->id);
            } else {
                $model = new AssetModel;
                $model->id = (int) $this->id;
            }

            $model->filename = $this->_filename;
            $model->volumeId = $this->getVolumeId();
            $model->folderId = (int) $this->folderId;
            $model->uploaderId = (int) $this->uploaderId ?: null;
            $model->kind = $this->kind;
            $model->size = (int) $this->size ?: null;
            $model->width = (int) $this->_width ?: $fallbackWidth;
            $model->height = (int) $this->_height ?: $fallbackHeight;
            $model->dateModified = Query::prepareDateForDb($this->dateModified);

            if (isset($this->_mimeType)) {
                $model->mimeType = $this->_mimeType;
            }

            if ($model->alt === null) {
                $model->alt = $this->alt;
            }

            if ($this->getHasFocalPoint()) {
                $focal = $this->getFocalPoint();
                $model->focalPoint = number_format($focal['x'], 4).';'.number_format($focal['y'], 4);
            } else {
                $model->focalPoint = null;
            }

            $model->save();
        }

        if (
            $this->propagating &&
            $this->propagatingFrom &&
            ! $isNew
        ) {
            /** @var self $from */
            $from = $this->propagatingFrom;

            if (
                $this->alt !== $from->alt &&
                $this->getAltTranslationKey() === $from->getAltTranslationKey()
            ) {
                $this->alt = $from->alt;
            }
        }

        DB::table(Table::ASSETS_SITES)
            ->upsert([
                'assetId' => $this->id,
                'siteId' => $this->siteId,
                'alt' => $this->alt,
            ], ['assetId', 'siteId']);

        parent::afterSave($isNew);
    }

    #[Override]
    public function beforeDelete(): bool
    {
        if (! parent::beforeDelete()) {
            return false;
        }

        // Update the asset record
        DB::table(Table::ASSETS)
            ->where('id', $this->id)
            ->update([
                'deletedWithVolume' => $this->deletedWithVolume,
                'keptFile' => $this->keepFileOnDelete,
            ]);

        return true;
    }

    #[Override]
    public function afterDelete(): void
    {
        if (! $this->keepFileOnDelete) {
            try {
                $this->getVolume()->sourceDisk()->delete($this->getPath());
            } catch (UnableToDeleteFile) {
                // NBD
            }
        }

        $this->deleteTransformData();
        parent::afterDelete();
    }

    #[Override]
    public function beforeRestore(): bool
    {
        // Only allow the asset to be restored if the file was kept on delete
        return $this->keptFile && parent::beforeRestore();
    }

    /** @return array<string, array<string, bool|int|string|null>> */
    #[Override]
    public function getHtmlAttributes(string $context): array
    {
        if ($this->isFolder) {
            $attributes = [
                'data' => [
                    'is-folder' => true,
                    'folder-id' => $this->folderId,
                    'folder-name' => $this->title,
                    'source-path' => Json::encode($this->sourcePath),
                    'has-children' => Folders::foldersExist(['parentId' => $this->folderId]),
                ],
            ];

            $user = currentUser();

            if ($user && Gate::check('moveFolderFrom', $this->getFolder())) {
                $attributes['data']['movable'] = true;
            }

            return $attributes;
        }

        return parent::getHtmlAttributes($context);
    }

    /** @return array<string, array<string, bool|int|string|null>> */
    #[Override]
    protected function htmlAttributes(string $context): array
    {
        $attributes = [
            'data' => [
                'kind' => $this->kind,
                'alt' => $this->alt,
                'filename' => $this->filename,
                'animated' => $this->couldHaveAnimatedThumb(),
            ],
        ];

        if ($this->kind === FileKind::Image->value) {
            $attributes['data']['image-width'] = $this->getWidth();
            $attributes['data']['image-height'] = $this->getHeight();
        }

        $volume = $this->getVolume();
        $imageEditable = $context === ElementSources::CONTEXT_INDEX && $this->getSupportsImageEditor();

        if ($volume->isTemporary() || Auth::id() === $this->uploaderId) {
            $attributes['data']['own-file'] = true;
            $movable = $replaceable = true;
        } else {
            $attributes['data']['peer-file'] = true;
            $movable = (
                Gate::check("savePeerAssets:$volume->uid") &&
                Gate::check("deletePeerAssets:$volume->uid")
            );
            $replaceable = Gate::check("replacePeerFiles:$volume->uid");
            $imageEditable = (
                $imageEditable &&
                (Gate::check("editPeerImages:$volume->uid"))
            );
        }

        if ($movable) {
            $attributes['data']['movable'] = true;
        }

        if ($replaceable) {
            $attributes['data']['replaceable'] = true;
        }

        if ($imageEditable) {
            $attributes['data']['editable-image'] = true;
        }

        if ($this->dateDeleted && $this->keptFile && Gate::check('save', $this)) {
            $attributes['data']['restorable'] = true;
        }

        return $attributes;
    }

    /**
     * Returns the width and height of the image.
     *
     * @param  ImageTransform|string|TransformConfig|null  $transform
     * @return array{int|null, int|null}
     */
    private function _dimensions(mixed $transform = null): array
    {
        if (! in_array($this->kind, [FileKind::Image->value, FileKind::Video->value], true)) {
            return [null, null];
        }

        if (! $this->_width || ! $this->_height) {
            if (
                $this->kind === FileKind::Image->value &&
                $this->ruleset->getScenario() !== AssetRules::SCENARIO_CREATE
            ) {
                Log::warning("Asset $this->id is missing its width or height", [__METHOD__]);
            }

            return [null, null];
        }

        if ($transform === null || ! ImageHelper::canManipulateAsImage($this->getExtension())) {
            return [$this->_width, $this->_height];
        }

        $transform = ImageTransformHelper::normalizeTransform($transform);

        return ImageHelper::targetDimensions(
            $this->_width,
            $this->_height,
            $transform->width,
            $transform->height,
            $transform->mode,
            $transform->upscale
        );
    }

    /**
     * Relocates the file after the element has been saved.
     *
     * @throws VolumeException if a file operation errored
     * @throws Exception if something else goes wrong
     */
    private function _relocateFile(): void
    {
        // Get the (new?) folder ID & filename
        if (isset($this->newLocation)) {
            [$folderId, $filename] = AssetsHelper::parseFileLocation($this->newLocation);
        } else {
            $folderId = $this->folderId;
            $filename = $this->_filename;
        }

        $hasNewFolder = $folderId !== $this->folderId;

        $tempPath = null;

        $oldFolder = $this->folderId ? Folders::getFolderById($this->folderId) : null;
        $oldVolume = $oldFolder?->getVolume();

        $newFolder = $hasNewFolder ? Folders::getFolderById($folderId) : $oldFolder;
        $newVolume = $hasNewFolder ? $newFolder->getVolume() : $oldVolume;

        $oldPath = $this->folderId ? $this->getPath() : null;
        $newPath = ($newFolder->path ? rtrim((string) $newFolder->path, '/').'/' : '').$filename;

        // Is this just a simple move/rename within the same volume?
        if (! isset($this->tempFilePath) && $oldFolder !== null && $oldFolder->volumeId === $newFolder->volumeId) {
            if (! $oldVolume->sourceDisk()->move($oldPath, $newPath)) {
                throw new FilesystemException("Unable to move $oldPath to $newPath");
            }
        } else {
            // Get the temp path
            if (isset($this->tempFilePath)) {
                if (! $this->_validateTempFilePath()) {
                    Log::info("Prevented saving $this->tempFilePath as an asset. It must be located within a temp directory or the project root (excluding system directories).");
                    throw new FileException(t('There was an error relocating the file.'));
                }

                $tempPath = $this->tempFilePath;
            } else {
                if ($oldVolume === null || $oldPath === null) {
                    throw new FileException(t('There was an error relocating the file.'));
                }

                $tempFilename = File::uniqueName($filename);
                $tempPath = Path::temp($tempFilename);
                AssetsHelper::downloadFile($oldVolume->sourceDisk(), $oldPath, $tempPath);
            }

            // Try to open a file stream
            if (($stream = fopen($tempPath, 'rb')) === false) {
                File::delete($tempPath);
                throw new FileException(t('Could not open file for streaming at {path}', ['path' => $tempPath]));
            }

            // Upload the file to the new location
            try {
                if (! $newVolume->sourceDisk()->writeStream($newPath, $stream, [
                    Filesystem::CONFIG_MIMETYPE => File::getMimeType($tempPath),
                ])) {
                    throw new FilesystemException("Unable to write stream to path: $newPath");
                }
            } catch (FilesystemException $exception) {
                report($exception);
                throw $exception;
            } finally {
                // If the volume has not already disconnected the stream, clean it up.
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            // if we got this far without an exception, it's okay to delete the file from the old volume
            if (
                $oldFolder &&
                ($oldFolder->id !== $newFolder->id || $oldPath !== $newPath)
            ) {
                // Delete the old file
                $oldVolume->sourceDisk()->delete($oldPath);
            }
        }

        if ($this->folderId) {
            // Nuke the transforms
            $this->deleteTransformData();
        }

        // Update file properties
        $this->setVolumeId($newFolder->volumeId);
        $this->folderId = $folderId;
        $this->folderPath = $newFolder->path;
        $this->_filename = $filename;
        $this->_volume = $newVolume;

        // If there was a new file involved, update file data.
        if ($tempPath && file_exists($tempPath)) {
            $this->kind = AssetsHelper::getFileKindByExtension($filename);

            if ($this->kind === FileKind::Image->value) {
                [$this->_width, $this->_height] = ImageHelper::imageSize($tempPath);
            } else {
                $this->_width = null;
                $this->_height = null;
            }

            $this->size = filesize($tempPath);
            $mtime = filemtime($tempPath);
            $this->dateModified = $mtime ? Date::createFromTimestampUTC($mtime) : null;

            // Delete the temp file
            File::delete($tempPath);
        }

        // Clear out the temp location properties
        $this->newLocation = null;
        $this->tempFilePath = null;
    }

    /**
     * Validates that the temp file path exists and is someplace safe.
     */
    private function _validateTempFilePath(): bool
    {
        $tempFilePath = realpath($this->tempFilePath);

        if ($tempFilePath === false || ! is_file($tempFilePath)) {
            return false;
        }

        $tempFilePath = File::normalizePath($tempFilePath);

        // Make sure it's within a known temp path, the project root, or storage/ folder
        $allowedRoots = [
            [Path::temp(), true],
            [Path::tempAssetUploads(), true],
            [sys_get_temp_dir(), true],
            [Aliases::get('@root', false), false],
            [Aliases::get('@storage', false), false],
        ];

        $inAllowedRoot = false;
        foreach ($allowedRoots as [$root, $isTempDir]) {
            $root = $this->_normalizeTempPath($root);
            if ($root !== false && str_starts_with($tempFilePath, $root)) {
                // If this is a known temp dir, we’re good here
                if ($isTempDir) {
                    return true;
                }
                $inAllowedRoot = true;
                break;
            }
        }
        if (! $inAllowedRoot) {
            return false;
        }

        // Make sure it's *not* within a system directory though
        $systemDirs = Path::system();
        $systemDirs = array_map($this->_normalizeTempPath(...), $systemDirs);
        $systemDirs = array_filter($systemDirs, fn ($value) => $value !== false);

        return array_all($systemDirs, fn ($dir) => ! str_starts_with($tempFilePath, (string) $dir));
    }

    /**
     * Returns a normalized temp path or false, if realpath fails.
     */
    private function _normalizeTempPath(string|false $path): string|false
    {
        if (! $path || ! ($path = realpath($path))) {
            return false;
        }

        return File::normalizePath($path).DIRECTORY_SEPARATOR;
    }

    private function deleteTransformData(): void
    {
        app(AssetTransformers::class)->invalidate($this);

        $dir = Path::imageEditorSources((string) $this->id);

        if (file_exists($dir)) {
            $files = glob($dir.'/[0-9]*/'.$this->id.'.[a-z]*');

            if (! is_array($files)) {
                Log::info("Could not list files in {$dir} when deleting resized asset versions.");
            } else {
                foreach ($files as $path) {
                    if (! File::delete($path)) {
                        Log::warning("Unable to delete the asset thumbnail \"{$path}\".", [__METHOD__]);
                    }
                }
            }
        }

        File::delete(Path::assetSources($this->id.'.'.pathinfo($this->getFilename(), PATHINFO_EXTENSION)));
    }

    /**
     * Attempts to extract alt text from XMP metadata embedded in an image file.
     * Checks Iptc4xmpCore:AltTextAccessibility first, then dc:description.
     */
    private function _getAltFromXmpMetadata(string $filePath): ?string
    {
        try {
            $xmp = null;

            if (Images::getIsImagick() && class_exists(Imagick::class)) {
                $imagick = new Imagick($filePath);
                $xmp = $imagick->getImageProfile('xmp') ?: null;
                $imagick->clear();
            }

            if ($xmp === null) {
                // Fall back to scanning the raw file for the XMP packet
                $handle = fopen($filePath, 'rb');
                if ($handle === false) {
                    return null;
                }
                $chunk = fread($handle, 131072); // 128KB covers the XMP packet in most images
                fclose($handle);

                if ($chunk !== false) {
                    $xmpStart = strpos($chunk, '<x:xmpmeta');
                    if ($xmpStart !== false) {
                        $xmpEnd = strpos($chunk, '</x:xmpmeta>', $xmpStart);
                        if ($xmpEnd !== false) {
                            $xmp = substr($chunk, $xmpStart, $xmpEnd - $xmpStart + strlen('</x:xmpmeta>'));
                        }
                    }
                }
            }

            if (empty($xmp)) {
                return null;
            }

            $dom = new DOMDocument;
            if (! @$dom->loadXML($xmp)) {
                return null;
            }

            $xpath = new DOMXPath($dom);
            $xpath->registerNamespace('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
            $xpath->registerNamespace('dc', 'http://purl.org/dc/elements/1.1/');
            $xpath->registerNamespace('Iptc4xmpCore', 'http://iptc.org/std/Iptc4xmpCore/1.0/xmlns/');
            $xpath->registerNamespace('Iptc4xmpExt', 'http://iptc.org/std/Iptc4xmpExt/2008-02-29/');

            // Try Iptc4xmpCore:AltTextAccessibility (LangAlt rdf:Alt/rdf:li structure)
            foreach ([
                '//Iptc4xmpCore:AltTextAccessibility/rdf:Alt/rdf:li',
                '//Iptc4xmpExt:AltTextAccessibility/rdf:Alt/rdf:li',
                '//Iptc4xmpCore:AltTextAccessibility[not(rdf:Alt)]',
                '//Iptc4xmpExt:AltTextAccessibility[not(rdf:Alt)]',
            ] as $query) {
                $nodes = $xpath->query($query);
                if ($nodes !== false) {
                    foreach ($nodes as $node) {
                        $value = trim($node->textContent);
                        if ($value !== '') {
                            return $value;
                        }
                    }
                }
            }

            // Try dc:description as an rdf:Alt structure
            $nodes = $xpath->query('//dc:description/rdf:Alt/rdf:li');
            if ($nodes !== false) {
                foreach ($nodes as $node) {
                    $value = trim($node->textContent);
                    if ($value !== '') {
                        return $value;
                    }
                }
            }

            // Try dc:description as a plain string value
            $nodes = $xpath->query('//dc:description[not(rdf:Alt)]');
            if ($nodes !== false) {
                foreach ($nodes as $node) {
                    $value = trim($node->textContent);
                    if ($value !== '') {
                        return $value;
                    }
                }
            }
        } catch (Throwable) {
            // Ignore errors and fall through to IPTC
        }

        return null;
    }

    /**
     * Attempts to extract alt text from IPTC Caption/Abstract (Iptc.Application2.Caption, field 2:120).
     */
    private function _getAltFromIptcMetadata(string $filePath): ?string
    {
        try {
            $imageInfo = [];
            @getimagesize($filePath, $imageInfo);

            if (! isset($imageInfo['APP13'])) {
                return null;
            }

            $iptc = iptcparse($imageInfo['APP13']);

            if (! empty($iptc['2#120'])) {
                $value = trim(implode(' ', $iptc['2#120']));
                if ($value !== '') {
                    return $value;
                }
            }
        } catch (Throwable) {
            // Ignore errors
        }

        return null;
    }
}
