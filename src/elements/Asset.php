<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements;

use Craft;
use craft\base\Element;
use craft\base\Field;
use craft\base\Fs;
use craft\base\FsInterface;
use craft\base\LocalFsInterface;
use craft\db\Query;
use craft\db\Table;
use craft\elements\actions\CopyReferenceTag;
use craft\elements\actions\CopyUrl;
use craft\elements\actions\DownloadAssetFile;
use craft\elements\actions\EditImage;
use craft\elements\actions\PreviewAsset;
use craft\elements\actions\RenameFile;
use craft\elements\actions\ReplaceFile;
use craft\elements\actions\Restore;
use craft\elements\conditions\assets\AssetCondition;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\db\AssetQuery;
use craft\elements\db\ElementQueryInterface;
use craft\errors\AssetException;
use craft\errors\FileException;
use craft\errors\FsException;
use craft\errors\ImageTransformException;
use craft\errors\VolumeException;
use craft\events\AssetEvent;
use craft\events\DefineAssetUrlEvent;
use craft\events\GenerateTransformEvent;
use craft\fieldlayoutelements\assets\AltField;
use craft\fs\Temp;
use craft\helpers\ArrayHelper;
use craft\helpers\Assets;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\FileHelper;
use craft\helpers\Html;
use craft\helpers\Image;
use craft\helpers\ImageTransforms;
use craft\helpers\Json;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\models\ImageTransform;
use craft\models\Volume;
use craft\models\VolumeFolder;
use craft\records\Asset as AssetRecord;
use craft\services\ElementSources;
use craft\validators\AssetLocationValidator;
use craft\validators\DateTimeValidator;
use craft\validators\StringValidator;
use craft\web\CpScreenResponseBehavior;
use DateTime;
use Throwable;
use Twig\Markup;
use yii\base\ErrorHandler;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\base\UnknownPropertyException;
use yii\validators\RequiredValidator;
use yii\web\Response;

/**
 * Asset represents an asset element.
 *
 * @property int|null $height the image height
 * @property int|null $width the image width
 * @property int|null $volumeId the volume ID
 * @property string $filename the filename (with extension)
 * @property string|array|null $focalPoint the focal point represented as an array with `x` and `y` keys, or null if it’s not an image
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
 * @property-read array $previewTargets
 * @property-read FsInterface $fs
 * @property-read string $titleTranslationKey
 * @property-read null|string $titleTranslationDescription
 * @property-read string $dataUrl
 * @property-read bool $isTitleTranslatable
 * @property-read string $previewHtml
 * @property-read string $imageTransformSourcePath
 * @property User|null $uploader
 * @property-read resource $stream
 * @property-write null|string|array|ImageTransform $transform
 * @property-read string $gqlTypeName
 * @property-read string|null $mimeType the file’s MIME type, if it can be determined
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Asset extends Element
{
    // Events
    // -------------------------------------------------------------------------

    /**
     * @event AssetEvent The event that is triggered before an asset is uploaded to volume.
     */
    public const EVENT_BEFORE_HANDLE_FILE = 'beforeHandleFile';

    /**
     * @event GenerateTransformEvent The event that is triggered before a transform is generated for an asset.
     * @since 4.0.0
     */
    public const EVENT_BEFORE_GENERATE_TRANSFORM = 'beforeGenerateTransform';

    /**
     * @event GenerateTransformEvent The event that is triggered after a transform is generated for an asset.
     * @since 4.0.0
     */
    public const EVENT_AFTER_GENERATE_TRANSFORM = 'afterGenerateTransform';

    /**
     * @event DefineAssetUrlEvent The event that is triggered when defining the asset’s URL.
     * @see getUrl()
     * @since 4.0.0
     */
    public const EVENT_DEFINE_URL = 'defineUrl';

    // Location error codes
    // -------------------------------------------------------------------------

    public const ERROR_DISALLOWED_EXTENSION = 'disallowed_extension';
    public const ERROR_FILENAME_CONFLICT = 'filename_conflict';

    // Validation scenarios
    // -------------------------------------------------------------------------

    /**
     * Validation scenario that should be used when the asset is only getting *moved*; not renamed.
     *
     * @since 3.7.1
     */
    public const SCENARIO_MOVE = 'move';
    public const SCENARIO_FILEOPS = 'fileOperations';
    public const SCENARIO_INDEX = 'index';
    public const SCENARIO_CREATE = 'create';
    public const SCENARIO_REPLACE = 'replace';

    // File kinds
    // -------------------------------------------------------------------------

    public const KIND_ACCESS = 'access';
    public const KIND_AUDIO = 'audio';
    /**
     * @since 3.6.0
     */
    public const KIND_CAPTIONS_SUBTITLES = 'captionsSubtitles';
    public const KIND_COMPRESSED = 'compressed';
    public const KIND_EXCEL = 'excel';
    public const KIND_FLASH = 'flash';
    public const KIND_HTML = 'html';
    public const KIND_ILLUSTRATOR = 'illustrator';
    public const KIND_IMAGE = 'image';
    public const KIND_JAVASCRIPT = 'javascript';
    public const KIND_JSON = 'json';
    public const KIND_PDF = 'pdf';
    public const KIND_PHOTOSHOP = 'photoshop';
    public const KIND_PHP = 'php';
    public const KIND_POWERPOINT = 'powerpoint';
    public const KIND_TEXT = 'text';
    public const KIND_VIDEO = 'video';
    public const KIND_WORD = 'word';
    public const KIND_XML = 'xml';
    public const KIND_UNKNOWN = 'unknown';

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Asset');
    }

    /**
     * @inheritdoc
     */
    public static function lowerDisplayName(): string
    {
        return Craft::t('app', 'asset');
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('app', 'Assets');
    }

    /**
     * @inheritdoc
     */
    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('app', 'assets');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle(): ?string
    {
        return 'asset';
    }

    /**
     * @inheritdoc
     */
    public static function hasContent(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasTitles(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function isLocalized(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     * @return AssetQuery The newly created [[AssetQuery]] instance.
     */
    public static function find(): AssetQuery
    {
        return new AssetQuery(static::class);
    }

    /**
     * @inheritdoc
     * @return AssetCondition
     */
    public static function createCondition(): ElementConditionInterface
    {
        return Craft::createObject(AssetCondition::class, [static::class]);
    }

    /**
     * @inheritdoc
     * @since 3.4.0
     */
    public static function eagerLoadingMap(array $sourceElements, string $handle): array|null|false
    {
        if ($handle === 'uploader') {
            // Get the source element IDs
            $sourceElementIds = ArrayHelper::getColumn($sourceElements, 'id');

            $map = (new Query())
                ->select(['id as source', 'uploaderId as target'])
                ->from([Table::ASSETS])
                ->where(['and', ['id' => $sourceElementIds], ['not', ['uploaderId' => null]]])
                ->all();

            return [
                'elementType' => User::class,
                'map' => $map,
            ];
        }

        return parent::eagerLoadingMap($sourceElements, $handle);
    }

    /**
     * @inheritdoc
     * @since 3.4.0
     */
    public function setEagerLoadedElements(string $handle, array $elements): void
    {
        if ($handle === 'uploader') {
            /** @var User|null $uploader */
            $uploader = $elements[0] ?? null;
            $this->setUploader($uploader);
        } else {
            parent::setEagerLoadedElements($handle, $elements);
        }
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public static function gqlTypeNameByContext(mixed $context): string
    {
        return $context->handle . '_Asset';
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public static function gqlScopesByContext(mixed $context): array
    {
        return ['volumes.' . $context->uid];
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public static function gqlMutationNameByContext(mixed $context): string
    {
        /** @var Volume $context */
        return 'save_' . $context->handle . '_Asset';
    }

    /**
     * @inheritdoc
     */
    protected static function defineSources(string $context): array
    {
        $volumes = Craft::$app->getVolumes();

        if ($context === ElementSources::CONTEXT_INDEX) {
            $volumeIds = $volumes->getViewableVolumeIds();
        } else {
            $volumeIds = $volumes->getAllVolumeIds();
        }

        $additionalCriteria = $context === ElementSources::CONTEXT_SETTINGS ? ['parentId' => ':empty:'] : [];

        $tree = Craft::$app->getAssets()->getFolderTreeByVolumeIds($volumeIds, $additionalCriteria);

        $sourceList = self::_assembleSourceList($tree, $context !== ElementSources::CONTEXT_SETTINGS, Craft::$app->getUser()->getIdentity());

        // Add the Temporary Uploads location, if that's not set to a real volume
        if (
            $context !== ElementSources::CONTEXT_SETTINGS &&
            !Craft::$app->getRequest()->getIsConsoleRequest() &&
            !Craft::$app->getProjectConfig()->get('assets.tempVolumeUid')
        ) {
            $temporaryUploadFolder = Craft::$app->getAssets()->getUserTemporaryUploadFolder();
            $temporaryUploadFolder->name = Craft::t('app', 'Temporary Uploads');
            $sourceList[] = self::_assembleSourceInfoForFolder($temporaryUploadFolder);
        }

        return $sourceList;
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    protected static function defineFieldLayouts(string $source): array
    {
        $fieldLayouts = [];
        if (
            preg_match('/^volume:(.+)$/', $source, $matches) &&
            ($volume = Craft::$app->getVolumes()->getVolumeByUid($matches[1])) &&
            $fieldLayout = $volume->getFieldLayout()
        ) {
            $fieldLayouts[] = $fieldLayout;
        }
        return $fieldLayouts;
    }

    /**
     * @inheritdoc
     */
    protected static function defineActions(string $source): array
    {
        $actions = [];

        if (preg_match('/^volume:([a-z0-9\-]+)/', $source, $matches)) {
            $volume = Craft::$app->getVolumes()->getVolumeByUid($matches[1]);
        } elseif (preg_match('/^folder:([a-z0-9\-]+)/', $source, $matches)) {
            $folder = Craft::$app->getAssets()->getFolderByUid($matches[1]);
            $volume = $folder?->getVolume();
        }

        // Only match the first folder ID - ignore nested folders
        if (isset($volume)) {
            $isTemp = $volume->getFs() instanceof Temp;

            $actions[] = [
                'type' => PreviewAsset::class,
                'label' => Craft::t('app', 'Preview file'),
            ];

            // Download
            $actions[] = DownloadAssetFile::class;

            $userSession = Craft::$app->getUser();
            if ($isTemp || $userSession->checkPermission("replaceFiles:$volume->uid")) {
                // Rename/Replace File
                $actions[] = RenameFile::class;
                $actions[] = ReplaceFile::class;
            }

            // Copy URL
            if ($volume->getFs()->hasUrls) {
                $actions[] = CopyUrl::class;
            }

            // Copy Reference Tag
            $actions[] = CopyReferenceTag::class;

            // Edit Image
            if ($isTemp || $userSession->checkPermission("editImages:$volume->uid")) {
                $actions[] = EditImage::class;
            }

            // Restore
            $actions[] = [
                'type' => Restore::class,
                'restorableElementsOnly' => true,
            ];
        }

        return $actions;
    }

    /**
     * @inheritdoc
     */
    protected static function defineSearchableAttributes(): array
    {
        return ['filename', 'extension', 'kind'];
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        return [
            'title' => Craft::t('app', 'Title'),
            'filename' => Craft::t('app', 'Filename'),
            'size' => Craft::t('app', 'File Size'),
            'kind' => Craft::t('app', 'File Kind'),
            [
                'label' => Craft::t('app', 'File Modification Date'),
                'orderBy' => 'dateModified',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'Date Uploaded'),
                'orderBy' => 'elements.dateCreated',
                'attribute' => 'dateCreated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'Date Updated'),
                'orderBy' => 'elements.dateUpdated',
                'attribute' => 'dateUpdated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'ID'),
                'orderBy' => 'elements.id',
                'attribute' => 'id',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        $attributes = [
            'filename' => ['label' => Craft::t('app', 'Filename')],
            'size' => ['label' => Craft::t('app', 'File Size')],
            'kind' => ['label' => Craft::t('app', 'File Kind')],
            'imageSize' => ['label' => Craft::t('app', 'Dimensions')],
            'width' => ['label' => Craft::t('app', 'Image Width')],
            'height' => ['label' => Craft::t('app', 'Image Height')],
            'alt' => ['label' => Craft::t('app', 'Alternative Text')],
            'location' => ['label' => Craft::t('app', 'Location')],
            'link' => ['label' => Craft::t('app', 'Link'), 'icon' => 'world'],
            'id' => ['label' => Craft::t('app', 'ID')],
            'uid' => ['label' => Craft::t('app', 'UID')],
            'dateModified' => ['label' => Craft::t('app', 'File Modified Date')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Uploaded')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
            'uploader' => ['label' => Craft::t('app', 'Uploaded By')],
        ];

        // Hide Author from Craft Solo
        if (Craft::$app->getEdition() !== Craft::Pro) {
            unset($attributes['uploader']);
        }

        return $attributes;
    }

    /**
     * @inheritdoc
     */
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

    /**
     * @inheritdoc
     */
    protected static function prepElementQueryForTableAttribute(ElementQueryInterface $elementQuery, string $attribute): void
    {
        if ($attribute === 'uploader') {
            $elementQuery->andWith('uploader');
        } else {
            parent::prepElementQueryForTableAttribute($elementQuery, $attribute);
        }
    }

    /**
     * Transforms an asset folder tree into a source list.
     *
     * @param array $folders
     * @param bool $includeNestedFolders
     * @param User|null $user
     * @return array
     */
    private static function _assembleSourceList(array $folders, bool $includeNestedFolders = true, ?User $user = null): array
    {
        $sources = [];

        foreach ($folders as $folder) {
            $sources[] = self::_assembleSourceInfoForFolder($folder, $includeNestedFolders, $user);
        }

        return $sources;
    }

    /**
     * Transforms an VolumeFolderModel into a source info array.
     *
     * @param VolumeFolder $folder
     * @param bool $includeNestedFolders
     * @param User|null $user
     * @return array
     */
    private static function _assembleSourceInfoForFolder(VolumeFolder $folder, bool $includeNestedFolders = true, ?User $user = null): array
    {
        $volume = $folder->getVolume();

        if ($volume->getFs() instanceof Temp) {
            $volumeHandle = 'temp';
        } elseif (!$folder->parentId) {
            $volumeHandle = $volume->handle ?? false;
        } else {
            $volumeHandle = false;
        }

        $userSession = Craft::$app->getUser();
        $canUpload = $userSession->checkPermission("saveAssets:$volume->uid");
        $canMoveTo = $canUpload && $userSession->checkPermission("deleteAssets:$volume->uid");
        $canMovePeerFilesTo = (
            $canMoveTo &&
            $userSession->checkPermission("savePeerAssets:$volume->uid") &&
            $userSession->checkPermission("deletePeerAssets:$volume->uid")
        );

        $source = [
            'key' => $folder->parentId ? "folder:$folder->uid" : "volume:$volume->uid",
            'label' => $folder->parentId ? $folder->name : Craft::t('site', $folder->name),
            'hasThumbs' => true,
            'criteria' => ['folderId' => $folder->id],
            'defaultSort' => ['dateCreated', 'desc'],
            'data' => [
                'volume-handle' => $volumeHandle,
                'folder-id' => $folder->id,
                'can-upload' => $folder->volumeId === null || $canUpload,
                'can-move-to' => $canMoveTo,
                'can-move-peer-files-to' => $canMovePeerFilesTo,
            ],
        ];

        if ($user && !$user->can("viewPeerAssets:$volume->uid")) {
            $source['criteria']['uploaderId'] = $user->id;
        }

        if ($includeNestedFolders) {
            $source['nested'] = self::_assembleSourceList($folder->getChildren(), true, $user);
        }

        return $source;
    }

    /**
     * @var int|null Folder ID
     */
    public ?int $folderId = null;

    /**
     * @var int|null The ID of the user who first added this asset (if known)
     */
    public ?int $uploaderId = null;

    /**
     * @var string|null Folder path
     */
    public ?string $folderPath = null;

    /**
     * @var string|null Kind
     */
    public ?string $kind = null;

    /**
     * @var string|null Alternative text
     * @since 4.0.0
     */
    public ?string $alt = null;

    /**
     * @var int|null Size
     */
    public ?int $size = null;

    /**
     * @var bool|null Whether the file was kept around when the asset was deleted
     */
    public ?bool $keptFile = null;

    /**
     * @var DateTime|null Date modified
     */
    public ?DateTime $dateModified = null;

    /**
     * @var string|null New file location
     */
    public ?string $newLocation = null;

    /**
     * @var string|null Location error code
     * @see AssetLocationValidator::validateAttribute()
     */
    public ?string $locationError = null;

    /**
     * @var string|null New filename
     */
    public ?string $newFilename = null;

    /**
     * @var int|null New folder id
     */
    public ?int $newFolderId = null;

    /**
     * @var string|null The temp file path
     */
    public ?string $tempFilePath = null;

    /**
     * @var bool Whether Asset should avoid filename conflicts when saved.
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
     * @see beforeDelete()
     */
    public bool $deletedWithVolume = false;

    /**
     * @var bool Whether the associated file should be preserved if the asset record is deleted.
     * @see beforeDelete()
     * @see afterDelete()
     */
    public bool $keepFileOnDelete = false;

    /**
     * @var int|null Volume ID
     */
    private ?int $_volumeId = null;

    /**
     * @var string Filename
     */
    private string $_filename;

    /**
     * @var int|null Width
     */
    private int|null $_width = null;

    /**
     * @var int|null Height
     */
    private int|null $_height = null;

    /**
     * @var array|null Focal point
     */
    private ?array $_focalPoint = null;

    /**
     * @var ImageTransform|null
     */
    private ?ImageTransform $_transform = null;

    /**
     * @var Volume|null
     */
    private ?Volume $_volume = null;

    /**
     * @var User|null
     */
    private ?User $_uploader = null;

    /**
     * @var int|null
     */
    private ?int $_oldVolumeId = null;

    /**
     * @inheritdoc
     */
    public function __toString(): string
    {
        try {
            if (isset($this->_transform) && ($url = (string)$this->getUrl())) {
                return $url;
            }
        } catch (Throwable $e) {
            ErrorHandler::convertExceptionToError($e);
        }

        return parent::__toString();
    }

    /**
     * Checks if a property is set.
     *
     * This method will check if $name is one of the following:
     * - a magic property supported by [[Element::__isset()]]
     * - an image transform handle
     *
     * @param string $name The property name
     * @return bool Whether the property is set
     */
    public function __isset($name): bool
    {
        return (
            parent::__isset($name) ||
            strncmp($name, 'transform:', 10) === 0 ||
            Craft::$app->getImageTransforms()->getTransformByHandle($name)
        );
    }

    /**
     * Returns a property value.
     *
     * This method will check if $name is one of the following:
     * - a magic property supported by [[Element::__get()]]
     * - an image transform handle
     *
     * @param string $name The property name
     * @return mixed The property value
     * @throws UnknownPropertyException if the property is not defined
     * @throws InvalidCallException if the property is write-only.
     */
    public function __get($name)
    {
        if (strncmp($name, 'transform:', 10) === 0) {
            return $this->copyWithTransform(substr($name, 10));
        }

        try {
            return parent::__get($name);
        } catch (UnknownPropertyException $e) {
            // Is $name a transform handle?
            if (($transform = Craft::$app->getImageTransforms()->getTransformByHandle($name)) !== null) {
                return $this->copyWithTransform($transform);
            }

            throw $e;
        }
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function init(): void
    {
        parent::init();

        if ($this->alt === '') {
            $this->alt = null;
        }

        $this->_oldVolumeId = $this->_volumeId;
    }

    /**
     * Returns the volume’s ID.
     *
     * @return int|null
     */
    public function getVolumeId(): ?int
    {
        return (int)$this->_volumeId ?: null;
    }

    /**
     * Sets the volume’s ID.
     *
     * @param int|null $id
     */
    public function setVolumeId(?int $id = null): void
    {
        if ($id !== $this->getVolumeId()) {
            $this->_volumeId = $id;
            $this->_volume = null;
        }
    }

    /**
     * @inheritdoc
     */
    public function afterValidate(): void
    {
        $scenario = $this->getScenario();

        if ($scenario === self::SCENARIO_LIVE) {
            $altElement = $this->getFieldLayout()->getFirstVisibleElementByType(AltField::class, $this);
            if ($altElement && $altElement->required) {
                (new RequiredValidator())->validateAttribute($this, 'alt');
            }
        }

        parent::afterValidate();
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['title'], StringValidator::class, 'max' => 255, 'disallowMb4' => true, 'on' => [self::SCENARIO_CREATE]];
        $rules[] = [['volumeId', 'folderId', 'width', 'height', 'size'], 'number', 'integerOnly' => true];
        $rules[] = [['dateModified'], DateTimeValidator::class];
        $rules[] = [['filename', 'kind'], 'required'];
        $rules[] = [['filename', 'newFilename', 'alt'], 'safe'];
        $rules[] = [['kind'], 'string', 'max' => 50];
        $rules[] = [['newLocation'], 'required', 'on' => [self::SCENARIO_CREATE, self::SCENARIO_MOVE, self::SCENARIO_FILEOPS]];
        $rules[] = [['tempFilePath'], 'required', 'on' => [self::SCENARIO_CREATE, self::SCENARIO_REPLACE]];

        // Validate the extension unless all we're doing is moving the file
        $rules[] = [
            ['newLocation'],
            AssetLocationValidator::class,
            'avoidFilenameConflicts' => $this->avoidFilenameConflicts,
            'except' => [self::SCENARIO_MOVE],
        ];
        $rules[] = [
            ['newLocation'],
            AssetLocationValidator::class,
            'avoidFilenameConflicts' => $this->avoidFilenameConflicts,
            'allowedExtensions' => '*',
            'on' => [self::SCENARIO_MOVE],
        ];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function scenarios(): array
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_INDEX] = [];

        return $scenarios;
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    protected function cacheTags(): array
    {
        $tags = [
            "volume:$this->_volumeId",
        ];

        // Did the volume just change?
        if ($this->_volumeId != $this->_oldVolumeId) {
            $tags[] = "volume:$this->_oldVolumeId";
        }

        return $tags;
    }

    /**
     * @inheritdoc
     */
    public function canView(User $user): bool
    {
        if (parent::canView($user)) {
            return true;
        }

        $volume = $this->getVolume();

        if ($this->uploaderId !== $user->id) {
            return $user->can("viewPeerAssets:$volume->uid");
        }

        if ($volume->getFs() instanceof Temp) {
            return true;
        }

        return $user->can("viewAssets:$volume->uid");
    }

    /**
     * @inheritdoc
     */
    public function canSave(User $user): bool
    {
        if (parent::canSave($user)) {
            return true;
        }

        $volume = $this->getVolume();

        if ($this->uploaderId !== $user->id) {
            return $user->can("savePeerAssets:$volume->uid");
        }

        return $user->can("saveAssets:$volume->uid");
    }

    /**
     * @inheritdoc
     */
    public function canDelete(User $user): bool
    {
        if (parent::canDelete($user)) {
            return true;
        }

        $volume = $this->getVolume();

        if ($volume->getFs() instanceof Temp) {
            return true;
        }

        if ($this->uploaderId !== $user->id) {
            return $user->can("deletePeerAssets:$volume->uid");
        }

        return $user->can("deleteAssets:$volume->uid");
    }

    /**
     * @inheritdoc
     * ---
     * ```php
     * $url = $asset->cpEditUrl;
     * ```
     * ```twig{2}
     * {% if asset.isEditable %}
     *   <a href="{{ asset.cpEditUrl }}">Edit</a>
     * {% endif %}
     * ```
     * @since 3.4.0
     */
    protected function cpEditUrl(): ?string
    {
        $volume = $this->getVolume();
        if ($volume->getFs() instanceof Temp) {
            return null;
        }

        $filename = $this->getFilename(false);
        $path = "assets/edit/$this->id-$filename";

        return UrlHelper::cpUrl($path);
    }

    /**
     * @inheritdoc
     */
    public function getPostEditUrl(): ?string
    {
        $volume = $this->getVolume();
        $uri = "assets/$volume->handle";
        if ($this->folderPath !== null) {
            $subfolders = ArrayHelper::filterEmptyStringsFromArray(explode('/', $this->folderPath));
            $uri .= sprintf('/%s', implode('/', $subfolders));
        }
        return UrlHelper::cpUrl($uri);
    }

    /**
     * @inheritdoc
     */
    public function prepareEditScreen(Response $response, string $containerId): void
    {
        $volume = $this->getVolume();
        $uri = "assets/$volume->handle";

        $crumbs = [
            [
                'label' => Craft::t('app', 'Assets'),
                'url' => UrlHelper::cpUrl('assets'),
            ],
            [
                'label' => Craft::t('site', $volume->name),
                'url' => UrlHelper::cpUrl($uri),
            ],
        ];

        if ($this->folderPath !== null) {
            $subfolders = ArrayHelper::filterEmptyStringsFromArray(explode('/', $this->folderPath));
            foreach ($subfolders as $subfolder) {
                $uri .= "/$subfolder";
                $crumbs[] = [
                    'label' => $subfolder,
                    'url' => UrlHelper::cpUrl($uri),
                ];
            }
        }

        /** @var Response|CpScreenResponseBehavior $response */
        $response->crumbs($crumbs);
    }

    /**
     * @inheritdoc
     */
    public function getAdditionalButtons(): string
    {
        $volume = $this->getVolume();
        $user = Craft::$app->getUser()->getIdentity();
        $view = Craft::$app->getView();

        $html = Html::beginTag('div', ['class' => 'btngroup']);

        if (($url = $this->getUrl()) !== null) {
            $html .= Html::a(Craft::t('app', 'View'), $url, [
                'class' => 'btn',
                'target' => '_blank',
                'data' => [
                    'icon' => 'preview',
                ],
            ]);
        }

        $html .= Html::button(Craft::t('app', 'Download'), [
            'id' => 'download-btn',
            'class' => 'btn',
            'data' => [
                'icon' => 'download',
            ],
            'aria' => [
                'label' => Craft::t('app', 'Download'),
            ],
        ]);

        $js = <<<JS
$('#download-btn').on('click', () => {
        const \$form = Craft.createForm().appendTo(Garnish.\$bod);
        \$form.append(Craft.getCsrfInput());
        $('<input/>', {type: 'hidden', name: 'action', value: 'assets/download-asset'}).appendTo(\$form);
        $('<input/>', {type: 'hidden', name: 'assetId', value: $this->id}).appendTo(\$form);
        $('<input/>', {type: 'submit', value: 'Submit'}).appendTo(\$form);
        \$form.submit();
        \$form.remove();
    });
JS;
        $view->registerJs($js);

        $html .= Html::endTag('div');

        if (
            $user->can("replaceFiles:$volume->uid") &&
            ($user->id === $this->uploaderId || $user->can("replacePeerFiles:$volume->uid"))
        ) {
            $html .= Html::button(Craft::t('app', 'Replace file'), [
                'id' => 'replace-btn',
                'class' => 'btn',
                'data' => [
                    'icon' => 'upload',
                ],
            ]);

            $dimensionsLabel = Html::encode(Craft::t('app', 'Dimensions'));
            $updatePreviewThumbJs = $this->_updatePreviewThumbJs();
            $js = <<<JS
$('#replace-btn').on('click', () => {
    const \$fileInput = $('<input/>', {type: 'file', name: 'replaceFile', class: 'replaceFile hidden'}).appendTo(Garnish.\$bod);
    const uploader = new Craft.Uploader(\$fileInput, {
        url: Craft.getActionUrl('assets/replace-file'),
        dropZone: null,
        fileInput: \$fileInput,
        paramName: 'replaceFile',
        events: {
            fileuploadstart: () => {
                $('#thumb-container').addClass('loading');
            },
            fileuploaddone: (event, data) => {
                if (data.result.error) {
                    $('#thumb-container').removeClass('loading');
                    alert(data.result.error);
                } else {
                    $('#new-filename').val(data.result.filename);
                    $('#file-size-value')
                        .text(data.result.formattedSize)
                        .attr('title', data.result.formattedSizeInBytes);
                    let \$dimensionsVal = $('#dimensions-value');
                    if (data.result.dimensions) {
                        if (!\$dimensionsVal.length) {
                            $(
                                '<div class="data">' +
                                '<dt class="heading">$dimensionsLabel</div>' +
                                '<dd id="dimensions-value" class="value"></div>' +
                                '</div>'
                            ).appendTo($('#details > .meta.read-only'));
                            \$dimensionsVal = $('#dimensions-value');
                        }
                        \$dimensionsVal.text(data.result.dimensions);
                    } else if (\$dimensionsVal.length) {
                        \$dimensionsVal.parent().remove();
                    }
                    $updatePreviewThumbJs
                    Craft.cp.runQueue();
                }
            }
        }
    });
    uploader.setParams({
        assetId: $this->id,
    });
    \$fileInput.click();
});
JS;
            $view->registerJs($js);
        }

        return $html . parent::getAdditionalButtons();
    }

    /**
     * Returns an `<img>` tag based on this asset.
     *
     * @param ImageTransform|string|array|null $transform The transform to use when generating the html.
     * @param string[]|null $sizes The widths/x-descriptors that should be used for the `srcset` attribute
     * (see [[getSrcset()]] for example syntaxes)
     * @return Markup|null
     * @throws InvalidArgumentException
     */
    public function getImg(mixed $transform = null, ?array $sizes = null): ?Markup
    {
        if ($this->kind !== self::KIND_IMAGE) {
            return null;
        }

        if ($transform) {
            $oldTransform = $this->_transform;
            $this->setTransform($transform);
        }

        $url = $this->getUrl();

        if ($url) {
            $img = Html::tag('img', '', [
                'src' => $url,
                'width' => $this->getWidth(),
                'height' => $this->getHeight(),
                'srcset' => $sizes ? $this->getSrcset($sizes) : false,
                'alt' => $this->alt ?? $this->title,
            ]);
        } else {
            $img = null;
        }

        if ($transform) {
            $this->setTransform($oldTransform);
        }

        return $img ? Template::raw($img) : null;
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
     * @param string[] $sizes
     * @param ImageTransform|string|array|null $transform A transform handle or configuration that should be applied to the image
     * @return string|false The `srcset` attribute value, or `false` if it can’t be determined
     * @throws InvalidArgumentException
     * @since 3.5.0
     */
    public function getSrcset(array $sizes, mixed $transform = null): string|false
    {
        $urls = $this->getUrlsBySize($sizes, $transform);

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
     * @param string[] $sizes
     * @param ImageTransform|string|array|null $transform A transform handle or configuration that should be applied to the image
     * @return array
     * @since 3.7.16
     */
    public function getUrlsBySize(array $sizes, mixed $transform = null): array
    {
        if ($this->kind !== self::KIND_IMAGE) {
            return [];
        }

        $urls = [];

        if (
            ($transform !== null || $this->_transform) &&
            Image::canManipulateAsImage($this->getExtension())
        ) {
            $transform = ImageTransforms::normalizeTransform($transform ?? $this->_transform);
        } else {
            $transform = null;
        }

        [$currentWidth, $currentHeight] = $this->_dimensions($transform);

        if (!$currentWidth || !$currentHeight) {
            return [];
        }

        foreach ($sizes as $size) {
            if ($size === '1x') {
                $urls[$size] = $this->getUrl($transform);
                continue;
            }

            [$value, $unit] = Assets::parseSrcsetSize($size);

            $sizeTransform = $transform ? $transform->toArray([
                'format',
                'height',
                'interlace',
                'mode',
                'position',
                'quality',
                'width',
            ]) : [];

            if ($unit === 'w') {
                $sizeTransform['width'] = (int)$value;
            } else {
                $sizeTransform['width'] = (int)ceil($currentWidth * $value);
            }

            // Only set the height if the current transform has a height set on it
            if ($transform && $transform->height) {
                if ($unit === 'w') {
                    $sizeTransform['height'] = (int)ceil($currentHeight * $sizeTransform['width'] / $currentWidth);
                } else {
                    $sizeTransform['height'] = (int)ceil($currentHeight * $value);
                }
            }

            $urls["$value$unit"] = $this->getUrl($sizeTransform);
        }

        return $urls;
    }

    /**
     * @inheritdoc
     */
    public function getIsTitleTranslatable(): bool
    {
        return ($this->getVolume()->titleTranslationMethod !== Field::TRANSLATION_METHOD_NONE);
    }

    /**
     * @inheritdoc
     */
    public function getTitleTranslationDescription(): ?string
    {
        return ElementHelper::translationDescription($this->getVolume()->titleTranslationMethod);
    }

    /**
     * @inheritdoc
     */
    public function getTitleTranslationKey(): string
    {
        $type = $this->getVolume();
        return ElementHelper::translationKey($this, $type->titleTranslationMethod, $type->titleTranslationKeyFormat);
    }

    /**
     * @inheritdoc
     */
    public function getFieldLayout(): ?FieldLayout
    {
        if (($fieldLayout = parent::getFieldLayout()) !== null) {
            return $fieldLayout;
        }

        $volume = $this->getVolume();
        return $volume->getFieldLayout();
    }

    /**
     * Returns the asset’s volume folder.
     *
     * @return VolumeFolder
     * @throws InvalidConfigException if [[folderId]] is missing or invalid
     */
    public function getFolder(): VolumeFolder
    {
        if (!isset($this->folderId)) {
            throw new InvalidConfigException('Asset is missing its folder ID');
        }

        if (($folder = Craft::$app->getAssets()->getFolderById($this->folderId)) === null) {
            throw new InvalidConfigException('Invalid folder ID: ' . $this->folderId);
        }

        return $folder;
    }

    /**
     * Returns the asset’s volume.
     *
     * @return Volume
     * @throws InvalidConfigException if [[volumeId]] is missing or invalid
     */
    public function getVolume(): Volume
    {
        if (isset($this->_volume)) {
            return $this->_volume;
        }

        $volumesService = Craft::$app->getVolumes();

        if (!isset($this->_volumeId)) {
            return $volumesService->getTemporaryVolume();
        }

        if (($volume = $volumesService->getVolumeById($this->_volumeId)) === null) {
            throw new InvalidConfigException('Invalid volume ID: ' . $this->_volumeId);
        }

        return $this->_volume = $volume;
    }

    /**
     * Returns the user that uploaded the asset, if known.
     *
     * @return User|null
     * @since 3.4.0
     */
    public function getUploader(): ?User
    {
        if (isset($this->_uploader)) {
            return $this->_uploader;
        }

        if (!isset($this->uploaderId)) {
            return null;
        }

        if (($this->_uploader = Craft::$app->getUsers()->getUserById($this->uploaderId)) === null) {
            // The uploader is probably soft-deleted. Just pretend no uploader is set
            return null;
        }

        return $this->_uploader;
    }

    /**
     * Sets the asset's uploader.
     *
     * @param User|null $uploader
     * @since 3.4.0
     */
    public function setUploader(?User $uploader = null): void
    {
        $this->_uploader = $uploader;
    }

    /**
     * Sets the transform.
     *
     * @param ImageTransform|string|array|null $transform A transform handle or configuration that should be applied to the image
     * @return Asset
     * @throws ImageTransformException if $transform is an invalid transform handle
     */
    public function setTransform(mixed $transform): Asset
    {
        $this->_transform = ImageTransforms::normalizeTransform($transform);

        return $this;
    }

    /**
     * Returns the element’s full URL.
     *
     * @param ImageTransform|string|array|null $transform A transform handle or configuration that should be applied to the
     * image If an array is passed, it can optionally include a `transform` key that defines a base transform
     * which the rest of the settings should be applied to.
     * @param bool|null $immediately Whether the image should be transformed immediately
     * @return string|null
     * @throws InvalidConfigException
     */
    public function getUrl(mixed $transform = null, ?bool $immediately = null): ?string
    {
        $url = $this->_url($transform, $immediately);

        // Give plugins/modules a chance to customize it
        if ($this->hasEventHandlers(self::EVENT_DEFINE_URL)) {
            $event = new DefineAssetUrlEvent([
                'url' => $url,
                'transform' => $transform,
                'asset' => $this,
            ]);
            $this->trigger(self::EVENT_DEFINE_URL, $event);
            // If DefineAssetUrlEvent::$url is set to null, only respect that if $handled is true
            if ($event->url !== null || $event->handled) {
                $url = $event->url;
            }
        }

        return $url !== null ? Html::encodeSpaces($url) : $url;
    }

    private function _url(mixed $transform = null, ?bool $immediately = null): ?string
    {
        $volume = $this->getVolume();

        $transform = $transform ?? $this->_transform;

        if ($transform === null || !Image::canManipulateAsImage(pathinfo($this->getFilename(), PATHINFO_EXTENSION))) {
            return Html::encodeSpaces(Assets::generateUrl($volume->getFs(), $this));
        }

        $fsNoUrls = !$transform && !$volume->getFs()->hasUrls;
        $noFolder = !$this->folderId;
        $transformNoUrl = $transform && !$volume->getTransformFs()->hasUrls;

        if ($fsNoUrls || $noFolder || $transformNoUrl) {
            return null;
        }

        $mimeType = $this->getMimeType();
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        if (
            ($mimeType === 'image/gif' && !$generalConfig->transformGifs) ||
            ($mimeType === 'image/svg+xml' && !$generalConfig->transformSvgs)
        ) {
            return Html::encodeSpaces(Assets::generateUrl($volume->getFs(), $this));
        }

        if ($transform) {
            if (is_array($transform)) {
                if (isset($transform['width'])) {
                    $transform['width'] = round((float)$transform['width']);
                }
                if (isset($transform['height'])) {
                    $transform['height'] = round((float)$transform['height']);
                }
            }

            $transform = ImageTransforms::normalizeTransform($transform);

            if ($immediately === null) {
                $immediately = Craft::$app->getConfig()->getGeneral()->generateTransformsBeforePageLoad;
            }

            try {
                if ($this->hasEventHandlers(self::EVENT_BEFORE_GENERATE_TRANSFORM)) {
                    $event = new GenerateTransformEvent([
                        'asset' => $this,
                        'transform' => $transform,
                    ]);

                    $this->trigger(self::EVENT_BEFORE_GENERATE_TRANSFORM, $event);

                    // If a plugin set the url, we'll just use that.
                    if ($event->url !== null) {
                        return Html::encodeSpaces($event->url);
                    }
                }

                $imageTransformer = $transform->getImageTransformer();
                $url = Html::encodeSpaces($imageTransformer->getTransformUrl($this, $transform, $immediately));

                if ($this->hasEventHandlers(self::EVENT_AFTER_GENERATE_TRANSFORM)) {
                    $event = new GenerateTransformEvent([
                        'asset' => $this,
                        'transform' => $transform,
                        'url' => $url,
                    ]);

                    $this->trigger(self::EVENT_AFTER_GENERATE_TRANSFORM, $event);
                }

                return $url;
            } catch (ImageTransformException $e) {
                Craft::warning("Couldn’t get image transform URL: {$e->getMessage()}", __METHOD__);
                Craft::$app->getErrorHandler()->logException($e);
                return null;
            }
        }

        return Html::encodeSpaces(Assets::generateUrl($volume->getFs(), $this));
    }

    /**
     * @inheritdoc
     */
    public function getThumbUrl(int $size): ?string
    {
        if ($this->getWidth() && $this->getHeight()) {
            [$width, $height] = Assets::scaledDimensions((int)$this->getWidth(), (int)$this->getHeight(), $size, $size);
        } else {
            $width = $height = $size;
        }

        return Craft::$app->getAssets()->getThumbUrl($this, $width, $height);
    }

    /**
     * @inheritdoc
     */
    public function getThumbAlt(): ?string
    {
        return $this->alt;
    }

    /**
     * @inheritdoc
     */
    public function getHasCheckeredThumb(): bool
    {
        return in_array(strtolower($this->getExtension()), ['png', 'gif', 'svg'], true);
    }

    /**
     * Returns preview thumb image HTML.
     *
     * @param int $desiredWidth
     * @param int $desiredHeight
     * @return string
     * @since 3.4.0
     */
    public function getPreviewThumbImg(int $desiredWidth, int $desiredHeight): string
    {
        $srcsets = [];
        [$width, $height] = Assets::scaledDimensions((int)$this->getWidth(), (int)$this->getHeight(), $desiredWidth, $desiredHeight);
        $thumbSizes = [
            [$width, $height],
            [$width * 2, $height * 2],
        ];
        $assetsService = Craft::$app->getAssets();

        foreach ($thumbSizes as [$width, $height]) {
            $url = $assetsService->getThumbUrl($this, $width, $height);
            $srcsets[] = sprintf('%s %sw', $url, $width);
        }

        return Html::tag('img', '', [
            'sizes' => "{$thumbSizes[0][0]}px",
            'srcset' => implode(', ', $srcsets),
            'alt' => $this->alt ?? $this->title,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getPreviewTargets(): array
    {
        return [];
    }

    /**
     * Returns the filename, with or without the extension.
     *
     * @param bool $withExtension
     * @return string
     * @throws InvalidConfigException if the filename isn’t set yet
     */
    public function getFilename(bool $withExtension = true): string
    {
        if (!isset($this->_filename)) {
            throw new InvalidConfigException('Asset not configured with its filename');
        }

        if ($withExtension) {
            return $this->_filename;
        }

        return pathinfo($this->_filename, PATHINFO_FILENAME);
    }

    /**
     * Sets the filename (with extension).
     *
     * @param string $filename
     * @since 4.0.0
     */
    public function setFilename(string $filename): void
    {
        $this->_filename = $filename;
    }

    /**
     * Returns the file extension.
     *
     * @return string
     */
    public function getExtension(): string
    {
        return pathinfo($this->_filename, PATHINFO_EXTENSION);
    }

    /**
     * Returns the file’s MIME type, if it can be determined.
     *
     * @return string|null
     */
    public function getMimeType(): ?string
    {
        // todo: maybe we should be passing this off to volume fs
        // so Local filesystems can call FileHelper::getMimeType() (uses magic file instead of ext)
        return FileHelper::getMimeTypeByExtension($this->_filename);
    }

    /**
     * Returns the image height.
     *
     * @param ImageTransform|string|array|null $transform A transform handle or configuration that should be applied to the image
     * @return int|null
     */

    public function getHeight(mixed $transform = null): ?int
    {
        return $this->_dimensions($transform)[1];
    }

    /**
     * Sets the image height.
     *
     * @param int|null $height the image height
     */
    public function setHeight(?int $height): void
    {
        $this->_height = $height;
    }

    /**
     * Returns the image width.
     *
     * @param array|string|ImageTransform|null $transform A transform handle or configuration that should be applied to the image
     * @return int|null
     */
    public function getWidth(array|string|ImageTransform $transform = null): ?int
    {
        return $this->_dimensions($transform)[0];
    }

    /**
     * Sets the image width.
     *
     * @param int|null $width the image width
     */
    public function setWidth(?int $width): void
    {
        $this->_width = $width;
    }

    /**
     * Returns the formatted file size, if known.
     *
     * @param int|null $decimals the number of digits after the decimal point
     * @param bool $short whether the size should be returned in short form (“kB” instead of “kilobytes”)
     * @return string|null
     * @since 3.4.0
     */
    public function getFormattedSize(?int $decimals = null, bool $short = true): ?string
    {
        if (!isset($this->size)) {
            return null;
        }
        if ($short) {
            return Craft::$app->getFormatter()->asShortSize($this->size, $decimals);
        }
        return Craft::$app->getFormatter()->asSize($this->size, $decimals);
    }

    /**
     * Returns the formatted file size in bytes, if known.
     *
     * @param bool $short whether the size should be returned in short form (“B” instead of “bytes”)
     * @return string|null
     * @since 3.4.0
     */
    public function getFormattedSizeInBytes(bool $short = true): ?string
    {
        $params = [
            'n' => $this->size,
            'nFormatted' => Craft::$app->getFormatter()->asDecimal($this->size),
        ];
        if ($short) {
            return Craft::t('yii', '{nFormatted} B', $params);
        }
        return Craft::t('yii', '{nFormatted} {n, plural, =1{byte} other{bytes}}', $params);
    }

    /**
     * Returns the image dimensions.
     *
     * @return string|null
     * @since 3.4.0
     */
    public function getDimensions(): ?string
    {
        $width = $this->getWidth();
        $height = $this->getHeight();
        if (!$width || !$height) {
            return null;
        }
        return $width . '×' . $height;
    }

    /**
     * Returns the asset's path in the volume.
     *
     * @param string|null $filename Filename to use. If not specified, the asset's filename will be used.
     * @return string
     */
    public function getPath(?string $filename = null): string
    {
        return $this->folderPath . ($filename ?: $this->_filename);
    }

    /**
     * Return the path where the source for this Asset's transforms should be.
     *
     * @return string
     */
    public function getImageTransformSourcePath(): string
    {
        $fs = $this->getFs();

        if ($fs instanceof LocalFsInterface) {
            return FileHelper::normalizePath($fs->getRootPath() . DIRECTORY_SEPARATOR . $this->getPath());
        }

        return Craft::$app->getPath()->getAssetSourcesPath() . DIRECTORY_SEPARATOR . $this->id . '.' . $this->getExtension();
    }

    /**
     * Get a temporary copy of the actual file.
     *
     * @return string
     * @throws VolumeException If unable to fetch file from volume.
     * @throws InvalidConfigException If no volume can be found.
     */
    public function getCopyOfFile(): string
    {
        $tempFilename = uniqid(pathinfo($this->_filename, PATHINFO_FILENAME), true) . '.' . $this->getExtension();
        $tempPath = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $tempFilename;
        Assets::downloadFile($this->getFs(), $this->getPath(), $tempPath);

        return $tempPath;
    }

    /**
     * Returns a stream of the actual file.
     *
     * @return resource
     * @throws InvalidConfigException if [[volumeId]] is missing or invalid
     * @throws FsException if a stream cannot be created
     */
    public function getStream()
    {
        return $this->getFs()->getFileStream($this->getPath());
    }

    /**
     * Returns the file’s contents.
     *
     * @return string
     * @throws InvalidConfigException if [[volumeId]] is missing or invalid
     * @throws AssetException if a stream could not be created
     * @since 3.0.6
     */
    public function getContents(): string
    {
        return stream_get_contents($this->getStream());
    }

    /**
     * Generates a base64-encoded [data URL](https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/Data_URIs) for the asset.
     *
     * @return string
     * @throws InvalidConfigException if [[volumeId]] is missing or invalid
     * @throws AssetException if a stream could not be created
     * @since 3.5.13
     */
    public function getDataUrl(): string
    {
        return Html::dataUrlFromString($this->getContents(), $this->getMimeType());
    }

    /**
     * Returns whether this asset can be edited by the image editor.
     *
     * @return bool
     */
    public function getSupportsImageEditor(): bool
    {
        $ext = $this->getExtension();
        return (strcasecmp($ext, 'svg') !== 0 && Image::canManipulateAsImage($ext));
    }

    /**
     * Returns whether a user-defined focal point is set on the asset.
     *
     * @return bool
     */
    public function getHasFocalPoint(): bool
    {
        return isset($this->_focalPoint);
    }

    /**
     * Returns the focal point represented as an array with `x` and `y` keys, or null if it’s not an image.
     *
     * @param bool $asCss whether the value should be returned in CSS syntax ("50% 25%") instead
     * @return array|string|null
     */
    public function getFocalPoint(bool $asCss = false): array|string|null
    {
        if (!in_array($this->kind, [self::KIND_IMAGE, self::KIND_VIDEO], true)) {
            return null;
        }

        $focal = $this->_focalPoint ?? ['x' => 0.5, 'y' => 0.5];

        if ($asCss) {
            return ($focal['x'] * 100) . '% ' . ($focal['y'] * 100) . '%';
        }

        return $focal;
    }

    /**
     * Sets the asset's focal point.
     *
     * @param array|string|null $value
     * @throws \InvalidArgumentException if $value is invalid
     */
    public function setFocalPoint(array|string|null $value): void
    {
        if (is_array($value)) {
            if (!isset($value['x'], $value['y'])) {
                throw new \InvalidArgumentException('$value should be a string or array with \'x\' and \'y\' keys.');
            }
            $value = [
                'x' => (float)$value['x'],
                'y' => (float)$value['y'],
            ];
        } elseif ($value !== null) {
            $focal = explode(';', $value);
            if (count($focal) !== 2) {
                throw new \InvalidArgumentException('$value should be a string or array with \'x\' and \'y\' keys.');
            }
            $value = [
                'x' => (float)$focal[0],
                'y' => (float)$focal[1],
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

    /**
     * @inheritdoc
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'uploader':
                $uploader = $this->getUploader();
                return $uploader ? Cp::elementHtml($uploader) : '';

            case 'filename':
                return Html::tag('span', Html::encode($this->_filename), [
                    'class' => 'break-word',
                ]);

            case 'kind':
                return Assets::getFileKindLabel($this->kind);

            case 'size':
                if (!isset($this->size)) {
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
                return ($size ? $size . 'px' : '');

            case 'location':
                return $this->locationHtml();
        }

        return parent::tableAttributeHtml($attribute);
    }

    /**
     * Returns the HTML for asset previews.
     *
     * @return string
     * @throws InvalidConfigException
     */
    public function getPreviewHtml(): string
    {
        $html = '';

        // See if we can show a thumbnail
        try {
            // Is the image editable, and is the user allowed to edit?
            $userSession = Craft::$app->getUser();

            $volume = $this->getVolume();
            $previewable = Craft::$app->getAssets()->getAssetPreviewHandler($this) !== null;
            $editable = (
                $this->getSupportsImageEditor() &&
                $userSession->checkPermission("editImages:$volume->uid") &&
                ($userSession->getId() == $this->uploaderId || $userSession->checkPermission("editPeerImages:$volume->uid"))
            );

            $previewThumbHtml =
                Html::beginTag('div', [
                    'id' => 'thumb-container',
                    'class' => array_filter([
                        'preview-thumb-container',
                        'button-fade',
                        $this->getHasCheckeredThumb() ? 'checkered' : null,
                    ]),
                ]) .
                Html::tag('div', $this->getPreviewThumbImg(350, 190), [
                    'class' => 'preview-thumb',
                ]) .
                Html::endTag('div'); // .preview-thumb-container;

            if ($previewable || $editable) {
                $isMobile = Craft::$app->getRequest()->isMobileBrowser(true);
                $imageButtonHtml = Html::beginTag('div', [
                    'class' => array_filter([
                        'image-actions',
                        'buttons',
                        ($isMobile ? 'is-mobile' : null),
                    ]),
                ]);
                $view = Craft::$app->getView();

                if ($previewable) {
                    $imageButtonHtml .= Html::button(Craft::t('app', 'Preview'), [
                        'id' => 'preview-btn',
                        'class' => ['btn', 'preview-btn'],
                    ]);

                    $previewBtnId = $view->namespaceInputId('preview-btn');
                    $settings = [];
                    $width = $this->getWidth();
                    $height = $this->getHeight();
                    if ($width && $height) {
                        $settings['startingWidth'] = $width;
                        $settings['startingHeight'] = $height;
                    }
                    $jsSettings = Json::encode($settings);
                    $js = <<<JS
$('#$previewBtnId').on('click', () => {
    new Craft.PreviewFileModal($this->id, null, $jsSettings);
});
JS;
                    $view->registerJs($js);
                }

                if ($editable) {
                    $imageButtonHtml .= Html::button(Craft::t('app', 'Edit Image'), [
                        'id' => 'edit-btn',
                        'class' => ['btn', 'edit-btn'],
                    ]);

                    $editBtnId = $view->namespaceInputId('edit-btn');
                    $updatePreviewThumbJs = $this->_updatePreviewThumbJs();
                    $js = <<<JS
$('#$editBtnId').on('click', () => {
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
    });
});
JS;
                    $view->registerJs($js);
                }

                $imageButtonHtml .= Html::endTag('div'); // .image-actions

                if (Craft::$app->getRequest()->isMobileBrowser(true)) {
                    $previewThumbHtml .= $imageButtonHtml;
                } else {
                    $previewThumbHtml = Html::appendToTag($previewThumbHtml, $imageButtonHtml);
                }
            }

            $html .= $previewThumbHtml;
        } catch (NotSupportedException) {
            // NBD
        }

        return $html;
    }

    private function _updatePreviewThumbJs(): string
    {
        $thumbContainerId = Craft::$app->getView()->namespaceInputId('thumb-container');
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

    /**
     * @inheritdoc
     */
    public function getSidebarHtml(bool $static): string
    {
        return implode("\n", [
            // Omit preview button on sidebar of slideouts
            $this->getPreviewHtml(),
            parent::getSidebarHtml($static),
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function metaFieldsHtml(bool $static): string
    {
        return implode("\n", [
            Cp::textFieldHtml([
                'label' => Craft::t('app', 'Filename'),
                'id' => 'new-filename',
                'name' => 'newFilename',
                'value' => $this->_filename,
                'errors' => $this->getErrors('newLocation'),
                'first' => true,
                'required' => true,
                'class' => ['text', 'filename'],
                'disabled' => $static,
            ]),
            parent::metaFieldsHtml($static),
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function metadata(): array
    {
        return [
            Craft::t('app', 'Location') => fn() => $this->locationHtml(),
            Craft::t('app', 'File size') => function() {
                $size = $this->getFormattedSize(0);
                if (!$size) {
                    return false;
                }
                $inBytes = $this->getFormattedSizeInBytes(false);
                return Html::tag('div', $size, [
                    'id' => 'file-size-value',
                    'title' => $inBytes,
                ]);
            },
            Craft::t('app', 'Uploaded by') => function() {
                $uploader = $this->getUploader();
                return $uploader ? Cp::elementHtml($uploader) : false;
            },
            Craft::t('app', 'Dimensions') => function() {
                $dimensions = $this->getDimensions();
                if (!$dimensions) {
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
        $uri = "assets/$volume->handle";
        $items = [
            Html::a(Craft::t('site', Html::encode($volume->name)), UrlHelper::cpUrl($uri)),
        ];
        if ($this->folderPath) {
            $subfolders = ArrayHelper::filterEmptyStringsFromArray(explode('/', $this->folderPath));
            foreach ($subfolders as $subfolder) {
                $uri .= "/$subfolder";
                $items[] = Html::a($subfolder, UrlHelper::cpUrl($uri));
            }
        }

        return Html::ul($items, [
            'encode' => false,
            'class' => 'path',
        ]);
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public function getGqlTypeName(): string
    {
        return static::gqlTypeNameByContext($this->getVolume());
    }

    /**
     * @inheritdoc
     */
    public function attributes(): array
    {
        $names = parent::attributes();
        $names[] = 'extension';
        $names[] = 'filename';
        $names[] = 'focalPoint';
        $names[] = 'hasFocalPoint';
        $names[] = 'height';
        $names[] = 'mimeType';
        $names[] = 'path';
        $names[] = 'volumeId';
        $names[] = 'width';
        return $names;
    }

    /**
     * @inheritdoc
     */
    public function extraFields(): array
    {
        $names = parent::extraFields();
        $names[] = 'folder';
        $names[] = 'volume';
        return $names;
    }

    /**
     * Returns a copy of the asset with the given transform applied to it.
     *
     * @param ImageTransform|string|array|null $transform The transform handle or configuration that should be applied to the image
     * @return Asset
     * @throws ImageTransformException if $transform is an invalid transform handle
     */
    public function copyWithTransform(mixed $transform): Asset
    {
        $model = clone $this;
        $model->setFieldValues($this->getFieldValues());
        $model->setTransform($transform);

        return $model;
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function beforeSave(bool $isNew): bool
    {
        if (!isset($this->_filename)) {
            if (isset($this->newLocation)) {
                [, $this->filename] = Assets::parseFileLocation($this->newLocation);
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
            [$folderId] = Assets::parseFileLocation($this->newLocation);
        } else {
            $folderId = $this->folderId;
        }

        // Fire a 'beforeHandleFile' event if we're going to be doing any file operations in afterSave()
        if (
            (isset($this->newLocation) || isset($this->tempFilePath)) &&
            $this->hasEventHandlers(self::EVENT_BEFORE_HANDLE_FILE)
        ) {
            $this->trigger(self::EVENT_BEFORE_HANDLE_FILE, new AssetEvent([
                'asset' => $this,
                'isNew' => !$this->id,
            ]));
        }

        // Set the kind based on filename
        $this->_setKind();

        // Give it a default title based on the file name, if it doesn't have a title yet
        if (!$this->id && !$this->title) {
            $this->title = Assets::filename2Title(pathinfo($this->_filename, PATHINFO_FILENAME));
        }

        // Set the field layout
        $volume = Craft::$app->getAssets()->getFolderById($folderId)->getVolume();

        if (!$volume->getFs() instanceof Temp) {
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
            $this->kind = Assets::getFileKindByExtension($this->_filename);
        }
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function afterSave(bool $isNew): void
    {
        if (!$this->propagating) {
            $isCpRequest = Craft::$app->getRequest()->getIsCpRequest();
            $sanitizeCpImageUploads = Craft::$app->getConfig()->getGeneral()->sanitizeCpImageUploads;

            if (
                in_array($this->getScenario(), [self::SCENARIO_REPLACE, self::SCENARIO_CREATE], true) &&
                Assets::getFileKindByExtension($this->tempFilePath) === static::KIND_IMAGE &&
                !($isCpRequest && !$sanitizeCpImageUploads)
            ) {
                Image::cleanImageByPath($this->tempFilePath);
            }

            // Relocate the file?
            if (isset($this->newLocation) || isset($this->tempFilePath)) {
                $this->_relocateFile();
            }

            // Get the asset record
            if (!$isNew) {
                $record = AssetRecord::findOne($this->id);

                if (!$record) {
                    throw new InvalidConfigException("Invalid asset ID: $this->id");
                }
            } else {
                $record = new AssetRecord();
                $record->id = (int)$this->id;
            }

            $record->filename = $this->_filename;
            $record->volumeId = $this->getVolumeId();
            $record->folderId = (int)$this->folderId;
            $record->uploaderId = (int)$this->uploaderId ?: null;
            $record->kind = $this->kind;
            $record->alt = $this->alt;
            $record->size = (int)$this->size ?: null;
            $record->width = (int)$this->_width ?: null;
            $record->height = (int)$this->_height ?: null;
            $record->dateModified = Db::prepareDateForDb($this->dateModified);

            if ($this->getHasFocalPoint()) {
                $focal = $this->getFocalPoint();
                $record->focalPoint = number_format($focal['x'], 4) . ';' . number_format($focal['y'], 4);
            } else {
                $record->focalPoint = null;
            }

            $record->save(false);
        }

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function beforeDelete(): bool
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        // Update the asset record
        Db::update(Table::ASSETS, [
            'deletedWithVolume' => $this->deletedWithVolume,
            'keptFile' => $this->keepFileOnDelete,
        ], [
            'id' => $this->id,
        ], [], false);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterDelete(): void
    {
        if (!$this->keepFileOnDelete) {
            $this->getFs()->deleteFile($this->getPath());
        }

        Craft::$app->getImageTransforms()->deleteAllTransformData($this);
        parent::afterDelete();
    }

    /**
     * @inheritdoc
     */
    public function beforeRestore(): bool
    {
        // Only allow the asset to be restored if the file was kept on delete
        return $this->keptFile && parent::beforeRestore();
    }

    /**
     * @inheritdoc
     */
    protected function htmlAttributes(string $context): array
    {
        $attributes = [
            'data' => [
                'kind' => $this->kind,
            ],
        ];

        if ($this->kind === self::KIND_IMAGE) {
            $attributes['data']['image-width'] = $this->getWidth();
            $attributes['data']['image-height'] = $this->getHeight();
        }

        $volume = $this->getVolume();
        $userSession = Craft::$app->getUser();
        $imageEditable = $context === ElementSources::CONTEXT_INDEX && $this->getSupportsImageEditor();

        if ($volume->getFs() instanceof Temp || $userSession->getId() == $this->uploaderId) {
            $attributes['data']['own-file'] = true;
            $movable = $replaceable = true;
        } else {
            $attributes['data']['peer-file'] = true;
            $movable = (
                $userSession->checkPermission("savePeerAssets:$volume->uid") &&
                $userSession->checkPermission("deletePeerAssets:$volume->uid")
            );
            $replaceable = $userSession->checkPermission("replacePeerFiles:$volume->uid");
            $imageEditable = (
                $imageEditable &&
                ($userSession->checkPermission("editPeerImages:$volume->uid"))
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

        if ($this->dateDeleted && $this->keptFile) {
            $attributes['data']['restorable'] = true;
        }

        return $attributes;
    }

    /**
     * Returns the filesystem the asset is stored in.
     *
     * @return FsInterface
     * @throws InvalidConfigException
     * @since 4.0.0
     */
    public function getFs(): FsInterface
    {
        return $this->getVolume()->getFs();
    }

    /**
     * Returns the width and height of the image.
     *
     * @param ImageTransform|string|array|null $transform
     * @return array
     */
    private function _dimensions(mixed $transform = null): array
    {
        if (!in_array($this->kind, [self::KIND_IMAGE, self::KIND_VIDEO], true)) {
            return [null, null];
        }

        if (!$this->_width || !$this->_height) {
            if ($this->getScenario() !== self::SCENARIO_CREATE) {
                Craft::warning("Asset $this->id is missing its width or height", __METHOD__);
            }

            return [null, null];
        }

        $transform = $transform ?? $this->_transform;

        if ($transform === null || !Image::canManipulateAsImage($this->getExtension())) {
            return [$this->_width, $this->_height];
        }

        $transform = ImageTransforms::normalizeTransform($transform);

        return Image::targetDimensions(
            $this->_width,
            $this->_height,
            $transform->width,
            $transform->height,
            $transform->mode
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
        $assetsService = Craft::$app->getAssets();

        // Get the (new?) folder ID & filename
        if (isset($this->newLocation)) {
            [$folderId, $filename] = Assets::parseFileLocation($this->newLocation);
        } else {
            $folderId = $this->folderId;
            $filename = $this->_filename;
        }

        $hasNewFolder = $folderId != $this->folderId;

        $tempPath = null;

        $oldFolder = $this->folderId ? $assetsService->getFolderById($this->folderId) : null;
        $oldVolume = $oldFolder?->getVolume();

        $newFolder = $hasNewFolder ? $assetsService->getFolderById($folderId) : $oldFolder;
        $newVolume = $hasNewFolder ? $newFolder->getVolume() : $oldVolume;

        $oldPath = $this->folderId ? $this->getPath() : null;
        $newPath = ($newFolder->path ? rtrim($newFolder->path, '/') . '/' : '') . $filename;

        // Is this just a simple move/rename within the same volume?
        if (!isset($this->tempFilePath) && $oldFolder !== null && $oldFolder->volumeId == $newFolder->volumeId) {
            $oldVolume->getFs()->renameFile($oldPath, $newPath);
        } else {
            // Get the temp path
            if (isset($this->tempFilePath)) {
                if (!$this->_validateTempFilePath()) {
                    Craft::warning("Prevented saving $this->tempFilePath as an asset. It must be located within a temp directory or the project root (excluding system directories).");
                    throw new FileException(Craft::t('app', "There was an error relocating the file."));
                }

                $tempPath = $this->tempFilePath;
            } else {
                $tempFilename = uniqid(pathinfo($filename, PATHINFO_FILENAME), true) . '.' . pathinfo($filename, PATHINFO_EXTENSION);
                $tempPath = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $tempFilename;
                Assets::downloadFile($oldVolume->getFs(), $oldPath, $tempPath);
            }

            // Try to open a file stream
            if (($stream = fopen($tempPath, 'rb')) === false) {
                if (file_exists($tempPath)) {
                    FileHelper::unlink($tempPath);
                }
                throw new FileException(Craft::t('app', 'Could not open file for streaming at {path}', ['path' => $tempPath]));
            }

            if ($this->folderId) {
                // Delete the old file
                $oldVolume->getFs()->deleteFile($oldPath);
            }

            // Upload the file to the new location
            try {
                $newVolume->getFs()->writeFileFromStream($newPath, $stream, [
                    Fs::CONFIG_MIMETYPE => FileHelper::getMimeType($tempPath),
                ]);
            } catch (VolumeException $exception) {
                Craft::$app->getErrorHandler()->logException($exception);
                throw $exception;
            } finally {
                // If the volume has not already disconnected the stream, clean it up.
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }

        if ($this->folderId) {
            // Nuke the transforms
            Craft::$app->getImageTransforms()->deleteAllTransformData($this);
        }

        // Update file properties
        $this->setVolumeId($newFolder->volumeId);
        $this->folderId = $folderId;
        $this->folderPath = $newFolder->path;
        $this->_filename = $filename;
        $this->_volume = $newVolume;

        // If there was a new file involved, update file data.
        if ($tempPath && file_exists($tempPath)) {
            $this->kind = Assets::getFileKindByExtension($filename);

            if ($this->kind === self::KIND_IMAGE) {
                [$this->_width, $this->_height] = Image::imageSize($tempPath);
            } else {
                $this->_width = null;
                $this->_height = null;
            }

            $this->size = filesize($tempPath);
            $mtime = filemtime($tempPath);
            $this->dateModified = $mtime ? new DateTime('@' . $mtime) : null;

            // Delete the temp file
            FileHelper::unlink($tempPath);
        }

        // Clear out the temp location properties
        $this->newLocation = null;
        $this->tempFilePath = null;
    }

    /**
     * Validates that the temp file path exists and is someplace safe.
     *
     * @return bool
     */
    private function _validateTempFilePath(): bool
    {
        $tempFilePath = realpath($this->tempFilePath);

        if ($tempFilePath === false || !is_file($tempFilePath)) {
            return false;
        }

        $tempFilePath = FileHelper::normalizePath($tempFilePath);

        // Make sure it's within a known temp path, the project root, or storage/ folder
        $pathService = Craft::$app->getPath();
        $allowedRoots = [
            [$pathService->getTempPath(), true],
            [$pathService->getTempAssetUploadsPath(), true],
            [sys_get_temp_dir(), true],
            [Craft::getAlias('@root', false), false],
            [Craft::getAlias('@storage', false), false],
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
        if (!$inAllowedRoot) {
            return false;
        }

        // Make sure it's *not* within a system directory though
        $systemDirs = $pathService->getSystemPaths();
        $systemDirs = array_map([$this, '_normalizeTempPath'], $systemDirs);
        $systemDirs = array_filter($systemDirs, function($value) {
            return ($value !== false);
        });

        foreach ($systemDirs as $dir) {
            if (str_starts_with($tempFilePath, $dir)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns a normalized temp path or false, if realpath fails.
     *
     * @param string|false $path
     * @return string|false
     */
    private function _normalizeTempPath(string|false $path): string|false
    {
        if (!$path || !($path = realpath($path))) {
            return false;
        }

        return FileHelper::normalizePath($path) . DIRECTORY_SEPARATOR;
    }
}
