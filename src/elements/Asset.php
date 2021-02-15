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
use craft\base\LocalVolumeInterface;
use craft\base\VolumeInterface;
use craft\db\Query;
use craft\db\Table;
use craft\elements\actions\CopyReferenceTag;
use craft\elements\actions\CopyUrl;
use craft\elements\actions\DeleteAssets;
use craft\elements\actions\DownloadAssetFile;
use craft\elements\actions\Edit;
use craft\elements\actions\EditImage;
use craft\elements\actions\PreviewAsset;
use craft\elements\actions\RenameFile;
use craft\elements\actions\ReplaceFile;
use craft\elements\db\AssetQuery;
use craft\elements\db\ElementQueryInterface;
use craft\errors\AssetException;
use craft\errors\AssetTransformException;
use craft\errors\FileException;
use craft\errors\VolumeObjectNotFoundException;
use craft\events\AssetEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Assets;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\FileHelper;
use craft\helpers\Html;
use craft\helpers\Image;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\models\AssetTransform;
use craft\models\VolumeFolder;
use craft\records\Asset as AssetRecord;
use craft\validators\AssetLocationValidator;
use craft\validators\DateTimeValidator;
use craft\validators\StringValidator;
use craft\volumes\Temp;
use DateTime;
use Twig\Markup;
use yii\base\ErrorHandler;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\base\UnknownPropertyException;

/**
 * Asset represents an asset element.
 *
 * @property int|float|null $height the image height
 * @property int|float|null $width the image width
 * @property int|null $volumeId the volume ID
 * @property string|array|null $focalPoint the focal point represented as an array with `x` and `y` keys, or null if it's not an image
 * @property-read Markup|null $img an `<img>` tag based on this asset
 * @property-read VolumeFolder $folder the asset’s volume folder
 * @property-read VolumeInterface $volume the asset’s volume
 * @property-read bool $hasFocalPoint whether a user-defined focal point is set on the asset
 * @property-read string $extension the file extension
 * @property-read string $path the asset's path in the volume
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
    const EVENT_BEFORE_HANDLE_FILE = 'beforeHandleFile';

    // Location error codes
    // -------------------------------------------------------------------------

    const ERROR_DISALLOWED_EXTENSION = 'disallowed_extension';
    const ERROR_FILENAME_CONFLICT = 'filename_conflict';

    // Validation scenarios
    // -------------------------------------------------------------------------

    const SCENARIO_FILEOPS = 'fileOperations';
    const SCENARIO_INDEX = 'index';
    const SCENARIO_CREATE = 'create';
    const SCENARIO_REPLACE = 'replace';

    // File kinds
    // -------------------------------------------------------------------------

    const KIND_ACCESS = 'access';
    const KIND_AUDIO = 'audio';
    /**
     * @since 3.6.0
     */
    const KIND_CAPTIONS_SUBTITLES = 'captionsSubtitles';
    const KIND_COMPRESSED = 'compressed';
    const KIND_EXCEL = 'excel';
    const KIND_FLASH = 'flash';
    const KIND_HTML = 'html';
    const KIND_ILLUSTRATOR = 'illustrator';
    const KIND_IMAGE = 'image';
    const KIND_JAVASCRIPT = 'javascript';
    const KIND_JSON = 'json';
    const KIND_PDF = 'pdf';
    const KIND_PHOTOSHOP = 'photoshop';
    const KIND_PHP = 'php';
    const KIND_POWERPOINT = 'powerpoint';
    const KIND_TEXT = 'text';
    const KIND_VIDEO = 'video';
    const KIND_WORD = 'word';
    const KIND_XML = 'xml';
    const KIND_UNKNOWN = 'unknown';

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
    public static function refHandle()
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
    public static function find(): ElementQueryInterface
    {
        return new AssetQuery(static::class);
    }

    /**
     * @inheritdoc
     * @since 3.4.0
     */
    public static function eagerLoadingMap(array $sourceElements, string $handle)
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
                'map' => $map
            ];
        }

        return parent::eagerLoadingMap($sourceElements, $handle);
    }

    /**
     * @inheritdoc
     * @since 3.4.0
     */
    public function setEagerLoadedElements(string $handle, array $elements)
    {
        if ($handle === 'uploader') {
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
    public static function gqlTypeNameByContext($context): string
    {
        return $context->handle . '_Asset';
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public static function gqlScopesByContext($context): array
    {
        return ['volumes.' . $context->uid];
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public static function gqlMutationNameByContext($context): string
    {
        /** @var VolumeInterface $context */
        return 'save_' . $context->handle . '_Asset';
    }

    /**
     * @inheritdoc
     */
    protected static function defineSources(string $context = null): array
    {
        $volumes = Craft::$app->getVolumes();

        if ($context === 'index') {
            $sourceIds = $volumes->getViewableVolumeIds();
        } else {
            $sourceIds = $volumes->getAllVolumeIds();
        }

        $additionalCriteria = $context === 'settings' ? ['parentId' => ':empty:'] : [];

        $tree = Craft::$app->getAssets()->getFolderTreeByVolumeIds($sourceIds, $additionalCriteria);

        $sourceList = self::_assembleSourceList($tree, $context !== 'settings', Craft::$app->getUser()->getIdentity());

        // Add the Temporary Uploads location, if that's not set to a real volume
        if (
            $context !== 'settings' &&
            !Craft::$app->getRequest()->getIsConsoleRequest() &&
            !Craft::$app->getProjectConfig()->get('assets.tempVolumeUid')
        ) {
            $temporaryUploadFolder = Craft::$app->getAssets()->getUserTemporaryUploadFolder();
            $temporaryUploadFolder->name = Craft::t('app', 'Temporary Uploads');
            $sourceList[] = self::_assembleSourceInfoForFolder($temporaryUploadFolder, false);
        }

        return $sourceList;
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public static function defineFieldLayouts(string $source): array
    {
        $fieldLayouts = [];
        if (
            preg_match('/^folder:(.+)$/', $source, $matches) &&
            ($folder = Craft::$app->getAssets()->getFolderByUid($matches[1])) &&
            $fieldLayout = $folder->getVolume()->getFieldLayout()
        ) {
            $fieldLayouts[] = $fieldLayout;
        }
        return $fieldLayouts;
    }

    /**
     * @inheritdoc
     */
    protected static function defineActions(string $source = null): array
    {
        $actions = [];

        // Only match the first folder ID - ignore nested folders
        if (
            preg_match('/^folder:([a-z0-9\-]+)/', $source, $matches) &&
            $folder = Craft::$app->getAssets()->getFolderByUid($matches[1])
        ) {
            $volume = $folder->getVolume();
            $isTemp = $volume instanceof Temp;

            $actions[] = [
                'type' => PreviewAsset::class,
                'label' => Craft::t('app', 'Preview file'),
            ];

            // Download
            $actions[] = DownloadAssetFile::class;

            // Edit
            $actions[] = [
                'type' => Edit::class,
                'label' => Craft::t('app', 'Edit asset'),
            ];

            $userSession = Craft::$app->getUser();
            if ($isTemp || $userSession->checkPermission("replaceFilesInVolume:$volume->uid")) {
                // Rename/Replace File
                $actions[] = RenameFile::class;
                $actions[] = ReplaceFile::class;
            }

            // Copy URL
            if ($volume->hasUrls) {
                $actions[] = CopyUrl::class;
            }

            // Copy Reference Tag
            $actions[] = [
                'type' => CopyReferenceTag::class,
                'elementType' => static::class,
            ];

            // Edit Image
            if ($isTemp || $userSession->checkPermission("editImagesInVolume:$volume->uid")) {
                $actions[] = EditImage::class;
            }

            // Delete
            if ($isTemp || $userSession->checkPermission("deleteFilesAndFoldersInVolume:$volume->uid")) {
                $actions[] = DeleteAssets::class;
            }
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
            'title' => ['label' => Craft::t('app', 'Title')],
            'filename' => ['label' => Craft::t('app', 'Filename')],
            'size' => ['label' => Craft::t('app', 'File Size')],
            'kind' => ['label' => Craft::t('app', 'File Kind')],
            'imageSize' => ['label' => Craft::t('app', 'Dimensions')],
            'width' => ['label' => Craft::t('app', 'Image Width')],
            'height' => ['label' => Craft::t('app', 'Image Height')],
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
    protected static function prepElementQueryForTableAttribute(ElementQueryInterface $elementQuery, string $attribute)
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
    private static function _assembleSourceList(array $folders, bool $includeNestedFolders = true, User $user = null): array
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
    private static function _assembleSourceInfoForFolder(VolumeFolder $folder, bool $includeNestedFolders = true, User $user = null): array
    {
        $volume = $folder->getVolume();

        if ($volume instanceof Temp) {
            $volumeHandle = 'temp';
        } else if (!$folder->parentId) {
            $volumeHandle = $volume->handle ?? false;
        } else {
            $volumeHandle = false;
        }

        $userSession = Craft::$app->getUser();
        $canUpload = $userSession->checkPermission("saveAssetInVolume:$volume->uid");
        $canMoveTo = $canUpload && $userSession->checkPermission("deleteFilesAndFoldersInVolume:$volume->uid");
        $canMovePeerFilesTo = (
            $canMoveTo &&
            $userSession->checkPermission("editPeerFilesInVolume:$volume->uid") &&
            $userSession->checkPermission("deletePeerFilesInVolume:$volume->uid")
        );

        $source = [
            'key' => 'folder:' . $folder->uid,
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
            ]
        ];

        if ($user) {
            if (!$user->can("viewPeerFilesInVolume:$volume->uid")) {
                $source['criteria']['uploaderId'] = $user->id;
            }
        }

        if ($includeNestedFolders) {
            $source['nested'] = self::_assembleSourceList($folder->getChildren(), true, $user);
        }

        return $source;
    }

    /**
     * @var int|null Folder ID
     */
    public $folderId;

    /**
     * @var int|null The ID of the user who first added this asset (if known)
     */
    public $uploaderId;

    /**
     * @var string|null Folder path
     */
    public $folderPath;

    /**
     * @var string|null Filename
     * @todo rename to private $_basename w/ getter & setter in 4.0; and getFilename() should not include the extension (to be like PATHINFO_FILENAME). We can add a getBasename() for getting the whole thing.
     */
    public $filename;

    /**
     * @var string|null Kind
     */
    public $kind;

    /**
     * @var int|null Size
     */
    public $size;

    /**
     * @var bool|null Whether the file was kept around when the asset was deleted
     */
    public $keptFile;

    /**
     * @var \DateTime|null Date modified
     */
    public $dateModified;

    /**
     * @var string|null New file location
     */
    public $newLocation;

    /**
     * @var string|null Location error code
     * @see AssetLocationValidator::validateAttribute()
     */
    public $locationError;

    /**
     * @var string|null New filename
     */
    public $newFilename;

    /**
     * @var int|null New folder id
     */
    public $newFolderId;

    /**
     * @var string|null The temp file path
     */
    public $tempFilePath;

    /**
     * @var bool Whether Asset should avoid filename conflicts when saved.
     */
    public $avoidFilenameConflicts = false;

    /**
     * @var string|null The suggested filename in case of a conflict.
     */
    public $suggestedFilename;

    /**
     * @var string|null The filename that was used that caused a conflict.
     */
    public $conflictingFilename;

    /**
     * @var bool Whether the asset was deleted along with its volume
     * @see beforeDelete()
     */
    public $deletedWithVolume = false;

    /**
     * @var bool Whether the associated file should be preserved if the asset record is deleted.
     * @see beforeDelete()
     * @see afterDelete()
     */
    public $keepFileOnDelete = false;

    /**
     * @var int|null Volume ID
     */
    private $_volumeId;

    /**
     * @var int|float|null Width
     */
    private $_width;

    /**
     * @var int|float|null Height
     */
    private $_height;

    /**
     * @var array|null Focal point
     */
    private $_focalPoint;

    /**
     * @var AssetTransform|null
     */
    private $_transform;

    /**
     * @var string
     */
    private $_transformSource = '';

    /**
     * @var VolumeInterface|null
     */
    private $_volume;

    /**
     * @var User|null
     */
    private $_uploader;

    /**
     * @var int|null
     */
    private $_oldVolumeId;

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        try {
            if ($this->_transform !== null && ($url = (string)$this->getUrl())) {
                return $url;
            }
            return parent::__toString();
        } catch (\Exception $e) {
            ErrorHandler::convertExceptionToError($e);
        }
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
            Craft::$app->getAssetTransforms()->getTransformByHandle($name)
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
            if (($transform = Craft::$app->getAssetTransforms()->getTransformByHandle($name)) !== null) {
                return $this->copyWithTransform($transform);
            }

            throw $e;
        }
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function init()
    {
        parent::init();
        $this->_oldVolumeId = $this->_volumeId;
    }

    /**
     * Returns the volume’s ID.
     *
     * @return int|null
     */
    public function getVolumeId()
    {
        return (int)$this->_volumeId ?: null;
    }

    /**
     * Sets the volume’s ID.
     *
     * @param int|null $id
     */
    public function setVolumeId(int $id = null)
    {
        if ($id !== $this->getVolumeId()) {
            $this->_volumeId = $id;
            $this->_volume = null;
        }
    }

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $attributes = parent::datetimeAttributes();
        $attributes[] = 'dateModified';
        return $attributes;
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
        $rules[] = [['kind'], 'string', 'max' => 50];
        $rules[] = [['newLocation'], AssetLocationValidator::class, 'avoidFilenameConflicts' => $this->avoidFilenameConflicts];
        $rules[] = [['newLocation'], 'required', 'on' => [self::SCENARIO_CREATE, self::SCENARIO_FILEOPS]];
        $rules[] = [['tempFilePath'], 'required', 'on' => [self::SCENARIO_CREATE, self::SCENARIO_REPLACE]];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_INDEX] = [];

        return $scenarios;
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function getCacheTags(): array
    {
        $tags = [
            "volume:$this->_volumeId"
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
    public function getIsEditable(): bool
    {
        $volume = $this->getVolume();
        $userSession = Craft::$app->getUser();
        return (
            $userSession->checkPermission("saveAssetInVolume:$volume->uid") &&
            ($userSession->getId() == $this->uploaderId || $userSession->checkPermission("editPeerFilesInVolume:$volume->uid"))
        );
    }

    /**
     * @inheritdoc
     * @since 3.5.15
     */
    public function getIsDeletable(): bool
    {
        $volume = $this->getVolume();

        if ($volume instanceof Temp) {
            return true;
        }

        $userSession = Craft::$app->getUser();
        return (
            $userSession->checkPermission("deleteFilesAndFoldersInVolume:$volume->uid") &&
            ($userSession->getId() == $this->uploaderId || $userSession->checkPermission("deletePeerFilesInVolume:$volume->uid"))
        );
    }

    /**
     * @inheritdoc
     * ---
     * ```php
     * $url = $asset->cpEditUrl;
     * ```
     * ```twig{2}
     * {% if asset.isEditable %}
     *     <a href="{{ asset.cpEditUrl }}">Edit</a>
     * {% endif %}
     * ```
     * @since 3.4.0
     */
    public function getCpEditUrl()
    {
        $volume = $this->getVolume();
        if ($volume instanceof Temp) {
            return null;
        }

        $filename = $this->getFilename(false);
        $path = "assets/$volume->handle/$this->id-$filename";

        $params = [];
        if (Craft::$app->getIsMultiSite()) {
            $params['site'] = $this->getSite()->handle;
        }

        return UrlHelper::cpUrl($path, $params);
    }

    /**
     * Returns an `<img>` tag based on this asset.
     *
     * @param mixed $transform The transform to use when generating the html.
     * @param string[]|null $sizes The widths/x-descriptors that should be used for the `srcset` attribute
     * (see [[getSrcset()]] for example syntaxes)
     * @return Markup|null
     * @throws InvalidArgumentException
     */
    public function getImg($transform = null, array $sizes = null)
    {
        if ($this->kind !== self::KIND_IMAGE) {
            return null;
        }

        $volume = $this->getVolume();

        if (!$volume->hasUrls) {
            return null;
        }

        if ($transform) {
            $oldTransform = $this->_transform;
            $this->setTransform($transform);
        }

        $img = Html::tag('img', '', [
            'src' => $this->getUrl(),
            'width' => $this->getWidth(),
            'height' => $this->getHeight(),
            'srcset' => $sizes ? $this->getSrcset($sizes) : false,
            'alt' => $this->title,
        ]);

        if (isset($oldTransform)) {
            $this->setTransform($oldTransform);
        }

        return Template::raw($img);
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
     * If you pass x-descriptors, it will be assumed that the image’s current width is the indented 1x width.
     * So if you pass `['1x', '2x']` on an image with a 100px-wide transform applied, you will get:
     *
     * ```
     * image-url@100w.ext,
     * image-url@200w.ext 2x
     * ```
     *
     * @param string[] $sizes
     * @return string|false The `srcset` attribute value, or `false` if it can’t be determined
     * @throws InvalidArgumentException
     * @since 3.5.0
     */
    public function getSrcset(array $sizes)
    {
        if ($this->kind !== self::KIND_IMAGE) {
            return false;
        }

        $srcset = [];

        if ($this->_transform && Image::canManipulateAsImage($this->getExtension())) {
            $transform = Craft::$app->getAssetTransforms()->normalizeTransform($this->_transform);
        } else {
            $transform = null;
        }

        [$currentWidth, $currentHeight] = $this->_dimensions($transform);

        if (!$currentWidth || !$currentHeight) {
            return false;
        }

        foreach ($sizes as $size) {
            if ($size === '1x') {
                $srcset[] = $this->getUrl();
                continue;
            }

            [$value, $unit] = Assets::parseSrcsetSize($size);

            $sizeTransform = $transform ? $transform->toArray() : [];

            // Having handle or name here will override dimensions, so we don't want that.
            unset($sizeTransform['handle'], $sizeTransform['name']);

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

            $srcset[] = $this->getUrl($sizeTransform) . " $value$unit";
        }

        return implode(', ', $srcset);
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
    public function getTitleTranslationDescription()
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
    public function getFieldLayout()
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
        if ($this->folderId === null) {
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
     * @return VolumeInterface
     * @throws InvalidConfigException if [[volumeId]] is missing or invalid
     */
    public function getVolume(): VolumeInterface
    {
        if ($this->_volume !== null) {
            return $this->_volume;
        }

        if ($this->_volumeId === null) {
            return new Temp();
        }

        if (($volume = Craft::$app->getVolumes()->getVolumeById($this->_volumeId)) === null) {
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
    public function getUploader()
    {
        if ($this->_uploader !== null) {
            return $this->_uploader;
        }

        if ($this->uploaderId === null) {
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
    public function setUploader(User $uploader = null)
    {
        $this->_uploader = $uploader;
    }

    /**
     * Sets the transform.
     *
     * @param AssetTransform|string|array|null $transform The transform that should be applied, if any. Can either be the handle of a named transform, or an array that defines the transform settings.
     * @return Asset
     * @throws AssetTransformException if $transform is an invalid transform handle
     */
    public function setTransform($transform): Asset
    {
        $this->_transform = Craft::$app->getAssetTransforms()->normalizeTransform($transform);

        return $this;
    }

    /**
     * Returns the element’s full URL.
     *
     * @param string|array|null $transform The transform that should be applied, if any. Can either be the handle of a named transform, or an array
     * that defines the transform settings. If an array is passed, it can optionally include a `transform` key that defines a base transform which
     * the rest of the settings should be applied to.
     * @param bool|null $generateNow Whether the transformed image should be generated immediately if it doesn’t exist. If `null`, it will be left
     * up to the `generateTransformsBeforePageLoad` config setting.
     * @return string|null
     * @throws InvalidConfigException
     */
    public function getUrl($transform = null, bool $generateNow = null)
    {
        $volume = $this->getVolume();

        if (!$volume->hasUrls || !$this->folderId) {
            return null;
        }

        if ($this->getMimeType() === 'image/gif' && !Craft::$app->getConfig()->getGeneral()->transformGifs) {
            return AssetsHelper::generateUrl($volume, $this);
        }

        // Normalize empty transform values
        $transform = $transform ?: null;

        if (is_array($transform)) {
            if (isset($transform['width'])) {
                $transform['width'] = round($transform['width']);
            }
            if (isset($transform['height'])) {
                $transform['height'] = round($transform['height']);
            }
            if (isset($transform['transform'])) {
                $assetTransformsService = Craft::$app->getAssetTransforms();
                $baseTransform = $assetTransformsService->normalizeTransform(ArrayHelper::remove($transform, 'transform'));
                $transform = $assetTransformsService->extendTransform($baseTransform, $transform);
            }
        }

        if ($transform === null && $this->_transform !== null) {
            $transform = $this->_transform;
        }

        try {
            return Craft::$app->getAssets()->getAssetUrl($this, $transform, $generateNow);
        } catch (VolumeObjectNotFoundException $e) {
            Craft::error("Could not determine asset's URL ($this->id): {$e->getMessage()}");
            Craft::$app->getErrorHandler()->logException($e);
            return UrlHelper::actionUrl('not-found');
        }
    }

    /**
     * @inheritdoc
     */
    public function getThumbUrl(int $size)
    {
        if ($this->getWidth() && $this->getHeight()) {
            [$width, $height] = Assets::scaledDimensions($this->getWidth(), $this->getHeight(), $size, $size);
        } else {
            $width = $height = $size;
        }

        return Craft::$app->getAssets()->getThumbUrl($this, $width, $height, false);
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
     * @param int $width
     * @param int $height
     * @return string
     * @throws NotSupportedException if the asset can't have a thumbnail, and $fallbackToIcon is `false`
     * @since 3.4.0
     */
    public function getPreviewThumbImg(int $width, int $height): string
    {
        $assetsService = Craft::$app->getAssets();
        $srcsets = [];
        [$width, $height] = Assets::scaledDimensions($this->getWidth() ?? 0, $this->getHeight() ?? 0, $width, $height);
        $thumbSizes = [
            [$width, $height],
            [$width * 2, $height * 2],
        ];
        foreach ($thumbSizes as [$width, $height]) {
            $thumbUrl = $assetsService->getThumbUrl($this, $width, $height, false, false);
            $srcsets[] = $thumbUrl . ' ' . $width . 'w';
        }

        return Html::tag('img', '', [
            'sizes' => "{$thumbSizes[0][0]}px",
            'srcset' => implode(', ', $srcsets),
            'alt' => $this->title,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getPreviewTargets(): array
    {
        return [];
    }

    /**
     * Returns the file name, with or without the extension.
     *
     * @param bool $withExtension
     * @return string
     */
    public function getFilename(bool $withExtension = true): string
    {
        if ($withExtension) {
            return $this->filename;
        }
        return pathinfo($this->filename, PATHINFO_FILENAME);
    }

    /**
     * Returns the file extension.
     *
     * @return string
     */
    public function getExtension(): string
    {
        return pathinfo($this->filename, PATHINFO_EXTENSION);
    }

    /**
     * Returns the file’s MIME type, if it can be determined.
     *
     * @return string|null
     */
    public function getMimeType()
    {
        // todo: maybe we should be passing this off to volume types
        // so Local volumes can call FileHelper::getMimeType() (uses magic file instead of ext)
        return FileHelper::getMimeTypeByExtension($this->filename);
    }

    /**
     * Returns the image height.
     *
     * @param AssetTransform|string|array|null $transform The transform that should be applied, if any. Can either be the handle of a named transform, or an array that defines the transform settings.
     * @return int|float|null
     */

    public function getHeight($transform = null)
    {
        return $this->_dimensions($transform)[1];
    }

    /**
     * Sets the image height.
     *
     * @param int|float|null $height the image height
     */
    public function setHeight($height)
    {
        $this->_height = $height;
    }

    /**
     * Returns the image width.
     *
     * @param AssetTransform|string|array|null $transform The optional transform handle for which to get thumbnail.
     * @return int|float|null
     */
    public function getWidth($transform = null)
    {
        return $this->_dimensions($transform)[0];
    }

    /**
     * Sets the image width.
     *
     * @param int|float|null $width the image width
     */
    public function setWidth($width)
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
    public function getFormattedSize(int $decimals = null, bool $short = true)
    {
        if ($this->size === null) {
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
    public function getFormattedSizeInBytes(bool $short = true)
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
    public function getDimensions()
    {
        $width = $this->getWidth();
        $height = $this->getHeight();
        if (!$width || !$height) {
            return null;
        }
        return "{$width}×{$height}";
    }

    /**
     * @return string
     */
    public function getTransformSource(): string
    {
        if (!$this->_transformSource) {
            Craft::$app->getAssetTransforms()->getLocalImageSource($this);
        }

        return $this->_transformSource;
    }

    /**
     * Set a source to use for transforms for this Assets File.
     *
     * @param string $uri
     */
    public function setTransformSource(string $uri)
    {
        $this->_transformSource = $uri;
    }

    /**
     * Returns the asset's path in the volume.
     *
     * @param string|null $filename Filename to use. If not specified, the asset's filename will be used.
     * @return string
     * @deprecated in 3.0.0-RC12
     */
    public function getUri(string $filename = null): string
    {
        Craft::$app->getDeprecator()->log(self::class . '::getUri()', '`' . self::class . '::getUri()` has been deprecated. Use `getPath()` instead.');

        return $this->getPath($filename);
    }

    /**
     * Returns the asset's path in the volume.
     *
     * @param string|null $filename Filename to use. If not specified, the asset's filename will be used.
     * @return string
     */
    public function getPath(string $filename = null): string
    {
        return $this->folderPath . ($filename ?: $this->filename);
    }

    /**
     * Return the path where the source for this Asset's transforms should be.
     *
     * @return string
     */
    public function getImageTransformSourcePath(): string
    {
        $volume = $this->getVolume();

        if ($volume instanceof LocalVolumeInterface) {
            return FileHelper::normalizePath($volume->getRootPath() . DIRECTORY_SEPARATOR . $this->getPath());
        }

        return Craft::$app->getPath()->getAssetSourcesPath() . DIRECTORY_SEPARATOR . $this->id . '.' . $this->getExtension();
    }

    /**
     * Get a temporary copy of the actual file.
     *
     * @return string
     */
    public function getCopyOfFile(): string
    {
        $tempFilename = uniqid(pathinfo($this->filename, PATHINFO_FILENAME), true) . '.' . $this->getExtension();
        $tempPath = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $tempFilename;
        $this->getVolume()->saveFileLocally($this->getPath(), $tempPath);

        return $tempPath;
    }

    /**
     * Returns a stream of the actual file.
     *
     * @return resource
     * @throws InvalidConfigException if [[volumeId]] is missing or invalid
     * @throws AssetException if a stream could not be created
     */
    public function getStream()
    {
        return $this->getVolume()->getFileStream($this->getPath());
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
     * Return whether the Asset has a URL.
     *
     * @return bool
     * @deprecated in 3.0.0-RC12. Use getVolume()->hasUrls instead.
     */
    public function getHasUrls(): bool
    {
        Craft::$app->getDeprecator()->log(self::class . '::getHasUrls()', '`' . self::class . '::getHasUrls()` has been deprecated. Use `getVolume()->hasUrls` instead.');

        $volume = $this->getVolume();
        return $volume && $volume->hasUrls;
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
     * Returns whether this asset can be previewed.
     *
     * @return bool
     * @deprecated in 3.4.0. Use [[\craft\services\Assets::getAssetPreviewHandler]] instead.
     */
    public function getSupportsPreview(): bool
    {
        Craft::$app->getDeprecator()->log(self::class . '::getSupportsPreview()', '`' . self::class . '::getSupportsPreview()` has been deprecated. Use `craft\services\Assets::getAssetPreview()` instead.');

        return \in_array($this->kind, [self::KIND_IMAGE, self::KIND_HTML, self::KIND_JAVASCRIPT, self::KIND_JSON], true);
    }

    /**
     * Returns whether a user-defined focal point is set on the asset.
     *
     * @return bool
     */
    public function getHasFocalPoint(): bool
    {
        return $this->_focalPoint !== null;
    }

    /**
     * Returns the focal point represented as an array with `x` and `y` keys, or null if it's not an image.
     *
     * @param bool $asCss whether the value should be returned in CSS syntax ("50% 25%") instead
     * @return array|string|null
     */
    public function getFocalPoint(bool $asCss = false)
    {
        if ($this->kind !== self::KIND_IMAGE) {
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
     * @param $value string|array|null
     * @throws \InvalidArgumentException if $value is invalid
     */
    public function setFocalPoint($value)
    {
        if (is_array($value)) {
            if (!isset($value['x'], $value['y'])) {
                throw new \InvalidArgumentException('$value should be a string or array with \'x\' and \'y\' keys.');
            }
            $value = [
                'x' => (float)$value['x'],
                'y' => (float)$value['y']
            ];
        } else if ($value !== null) {
            $focal = explode(';', $value);
            if (count($focal) !== 2) {
                throw new \InvalidArgumentException('$value should be a string or array with \'x\' and \'y\' keys.');
            }
            $value = [
                'x' => (float)$focal[0],
                'y' => (float)$focal[1]
            ];
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
                return Html::tag('span', Html::encode($this->filename), [
                    'class' => 'break-word',
                ]);

            case 'kind':
                return AssetsHelper::getFileKindLabel($this->kind);

            case 'size':
                if ($this->size === null) {
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
        }

        return parent::tableAttributeHtml($attribute);
    }

    /**
     * @inheritdoc
     */
    public function getEditorHtml(): string
    {
        $view = Craft::$app->getView();

        if (!$this->fieldLayoutId) {
            $this->fieldLayoutId = Craft::$app->getRequest()->getBodyParam('defaultFieldLayoutId');
        }

        $html = '';

        // See if we can show a thumbnail
        try {
            // Is the image editable, and is the user allowed to edit?
            $userSession = Craft::$app->getUser();

            $volume = $this->getVolume();

            $editable = (
                $this->getSupportsImageEditor() &&
                $userSession->checkPermission("editImagesInVolume:$volume->uid") &&
                ($userSession->getId() == $this->uploaderId || $userSession->checkPermission("editPeerImagesInVolume:$volume->uid"))
            );

            $html .= '<div class="preview-thumb-container' . ($editable ? ' editable' : '') . '">' .
                '<div class="preview-thumb">' .
                $this->getPreviewThumbImg(380, 190) .
                '</div>';

            if ($editable) {
                $html .= '<div class="buttons"><div class="btn">' . Craft::t('app', 'Edit') . '</div></div>';
            }

            $html .= '</div>';
        } catch (NotSupportedException $e) {
            // NBD
        }

        $html .= Cp::textFieldHtml([
            'label' => Craft::t('app', 'Filename'),
            'id' => 'newFilename',
            'name' => 'newFilename',
            'value' => $this->filename,
            'errors' => $this->getErrors('newLocation'),
            'first' => true,
            'required' => true,
            'class' => ['renameHelper', 'text'],
        ]);

        $html .= parent::getEditorHtml();

        return $html;
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
    public function attributes()
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
    public function extraFields()
    {
        $names = parent::extraFields();
        $names[] = 'folder';
        $names[] = 'volume';
        return $names;
    }

    /**
     * Returns a copy of the asset with the given transform applied to it.
     *
     * @param AssetTransform|string|array|null $transform
     * @return Asset
     * @throws AssetTransformException if $transform is an invalid transform handle
     */
    public function copyWithTransform($transform): Asset
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
        // newFolderId/newFilename => newLocation.
        if ($this->newFolderId !== null || $this->newFilename !== null) {
            $folderId = $this->newFolderId ?: $this->folderId;
            $filename = $this->newFilename ?: $this->filename;
            $this->newLocation = "{folder:$folderId}$filename";
            $this->newFolderId = $this->newFilename = null;
        }

        // Get the (new?) folder ID
        if ($this->newLocation !== null) {
            [$folderId] = AssetsHelper::parseFileLocation($this->newLocation);
        } else {
            $folderId = $this->folderId;
        }

        // Fire a 'beforeHandleFile' event if we're going to be doing any file operations in afterSave()
        if (
            ($this->newLocation !== null || $this->tempFilePath !== null) &&
            $this->hasEventHandlers(self::EVENT_BEFORE_HANDLE_FILE)
        ) {
            $this->trigger(self::EVENT_BEFORE_HANDLE_FILE, new AssetEvent([
                'asset' => $this,
                'isNew' => !$this->id
            ]));
        }

        // Set the kind based on filename, if not set already
        if ($this->kind === null && $this->filename !== null) {
            $this->kind = AssetsHelper::getFileKindByExtension($this->filename);
        }

        // Give it a default title based on the file name, if it doesn't have a title yet
        if (!$this->id && !$this->title) {
            $this->title = AssetsHelper::filename2Title(pathinfo($this->filename, PATHINFO_FILENAME));
        }

        // Set the field layout
        $volume = Craft::$app->getAssets()->getFolderById($folderId)->getVolume();

        if (!$volume instanceof Temp) {
            $this->fieldLayoutId = $volume->fieldLayoutId;
        }

        return parent::beforeSave($isNew);
    }

    /**
     * @inheritdoc
     * @throws Exception if the asset isn't new but doesn't have a row in the `assets` table for some reason
     */
    public function afterSave(bool $isNew)
    {
        if (!$this->propagating) {
            $isCpRequest = Craft::$app->getRequest()->getIsCpRequest();
            $sanitizeCpImageUploads = Craft::$app->getConfig()->getGeneral()->sanitizeCpImageUploads;
            
            if (
                \in_array($this->getScenario(), [self::SCENARIO_REPLACE, self::SCENARIO_CREATE], true) &&
                AssetsHelper::getFileKindByExtension($this->tempFilePath) === static::KIND_IMAGE &&
                !($isCpRequest && !$sanitizeCpImageUploads)
            ) {
                Image::cleanImageByPath($this->tempFilePath);
            }

            // Relocate the file?
            if ($this->newLocation !== null || $this->tempFilePath !== null) {
                $this->_relocateFile();
            }

            // Get the asset record
            if (!$isNew) {
                $record = AssetRecord::findOne($this->id);

                if (!$record) {
                    throw new Exception('Invalid asset ID: ' . $this->id);
                }
            } else {
                $record = new AssetRecord();
                $record->id = (int)$this->id;
            }

            $record->filename = $this->filename;
            $record->volumeId = $this->getVolumeId();
            $record->folderId = (int)$this->folderId;
            $record->uploaderId = (int)$this->uploaderId ?: null;
            $record->kind = $this->kind;
            $record->size = (int)$this->size ?: null;
            $record->width = (int)$this->_width ?: null;
            $record->height = (int)$this->_height ?: null;
            $record->dateModified = $this->dateModified;

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
    public function afterDelete()
    {
        if (!$this->keepFileOnDelete) {
            $this->getVolume()->deleteFile($this->getPath());
        }

        Craft::$app->getAssetTransforms()->deleteAllTransformData($this);
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
        $attributes = [];

        if ($this->kind === self::KIND_IMAGE) {
            $attributes['data-image-width'] = $this->getWidth();
            $attributes['data-image-height'] = $this->getHeight();
        }

        $volume = $this->getVolume();
        $userSession = Craft::$app->getUser();
        $imageEditable = $context === 'index' && $this->getSupportsImageEditor();

        if ($volume instanceof Temp || $userSession->getId() == $this->uploaderId) {
            $attributes['data-own-file'] = null;
            $movable = $replaceable = true;
        } else {
            $attributes['data-peer-file'] = null;
            $movable = (
                $userSession->checkPermission("editPeerFilesInVolume:$volume->uid") &&
                $userSession->checkPermission("deletePeerFilesInVolume:$volume->uid")
            );
            $replaceable = $userSession->checkPermission("replacePeerFilesInVolume:$volume->uid");
            $imageEditable = (
                $imageEditable &&
                ($userSession->checkPermission("editPeerImagesInVolume:$volume->uid"))
            );
        }

        if ($movable) {
            $attributes['data-movable'] = null;
        }

        if ($replaceable) {
            $attributes['data-replaceable'] = null;
        }

        if ($imageEditable) {
            $attributes['data-editable-image'] = null;
        }

        return $attributes;
    }

    /**
     * Returns whether the current user can move/rename the asset.
     *
     * @return bool
     */
    private function _isMovable(): bool
    {
        $userSession = Craft::$app->getUser();
        if ($userSession->getId() == $this->uploaderId) {
            return true;
        }

        $volume = $this->getVolume();
        return (
            $userSession->checkPermission("editPeerFilesInVolume:$volume->uid") &&
            $userSession->checkPermission("deletePeerFilesInVolume:$volume->uid")
        );
    }

    /**
     * Returns the width and height of the image.
     *
     * @param AssetTransform|string|array|null $transform
     * @return array
     */
    private function _dimensions($transform = null): array
    {
        if ($this->kind !== self::KIND_IMAGE) {
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

        $transform = Craft::$app->getAssetTransforms()->normalizeTransform($transform);

        if ($this->_width < $transform->width && $this->_height < $transform->height && !Craft::$app->getConfig()->getGeneral()->upscaleImages) {
            $transformRatio = $transform->width / $transform->height;
            $imageRatio = $this->_width / $this->_height;

            if ($transform->mode !== 'crop' || $imageRatio === $transformRatio) {
                return [$this->_width, $this->_height];
            }

            return $transformRatio > 1 ? [$this->_width, round($this->_height / $transformRatio)] : [round($this->_width * $transformRatio), $this->_height];
        }

        [$width, $height] = Image::calculateMissingDimension($transform->width, $transform->height, $this->_width, $this->_height);

        // Special case for 'fit' since that's the only one whose dimensions vary from the transform dimensions
        if ($transform->mode === 'fit') {
            $factor = max($this->_width / $width, $this->_height / $height);
            $width = (int)round($this->_width / $factor);
            $height = (int)round($this->_height / $factor);
        }

        return [$width, $height];
    }

    /**
     * Relocates the file after the element has been saved.
     *
     * @throws FileException if the file is being moved but cannot be read
     */
    private function _relocateFile()
    {
        $assetsService = Craft::$app->getAssets();

        // Get the (new?) folder ID & filename
        if ($this->newLocation !== null) {
            [$folderId, $filename] = AssetsHelper::parseFileLocation($this->newLocation);
        } else {
            $folderId = $this->folderId;
            $filename = $this->filename;
        }

        $hasNewFolder = $folderId != $this->folderId;

        $tempPath = null;

        $oldFolder = $this->folderId ? $assetsService->getFolderById($this->folderId) : null;
        $oldVolume = $oldFolder ? $oldFolder->getVolume() : null;

        $newFolder = $hasNewFolder ? $assetsService->getFolderById($folderId) : $oldFolder;
        $newVolume = $hasNewFolder ? $newFolder->getVolume() : $oldVolume;

        $oldPath = $this->folderId ? $this->getPath() : null;
        $newPath = ($newFolder->path ? rtrim($newFolder->path, '/') . '/' : '') . $filename;

        // Is this just a simple move/rename within the same volume?
        if ($this->tempFilePath === null && $oldFolder !== null && $oldFolder->volumeId == $newFolder->volumeId) {
            $oldVolume->renameFile($oldPath, $newPath);
        } else {
            // Get the temp path
            if ($this->tempFilePath !== null) {
                $tempPath = $this->tempFilePath;
            } else {
                $tempFilename = uniqid(pathinfo($filename, PATHINFO_FILENAME), true) . '.' . pathinfo($filename, PATHINFO_EXTENSION);
                $tempPath = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $tempFilename;
                $oldVolume->saveFileLocally($oldPath, $tempPath);
            }

            // Try to open a file stream
            if (($stream = fopen($tempPath, 'rb')) === false) {
                FileHelper::unlink($tempPath);
                throw new FileException(Craft::t('app', 'Could not open file for streaming at {path}', ['path' => $tempPath]));
            }

            if ($this->folderId) {
                // Delete the old file
                $oldVolume->deleteFile($oldPath);
            }

            // Upload the file to the new location
            $newVolume->createFileByStream($newPath, $stream, [
                'mimetype' => FileHelper::getMimeType($tempPath)
            ]);

            // Rackspace will disconnect the stream automatically
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        if ($this->folderId) {
            // Nuke the transforms
            Craft::$app->getAssetTransforms()->deleteAllTransformData($this);
        }

        // Update file properties
        $this->setVolumeId($newFolder->volumeId);
        $this->folderId = $folderId;
        $this->folderPath = $newFolder->path;
        $this->filename = $filename;
        $this->_volume = $newVolume;

        // If there was a new file involved, update file data.
        if ($tempPath) {
            $this->kind = AssetsHelper::getFileKindByExtension($filename);

            if ($this->kind === self::KIND_IMAGE) {
                [$this->_width, $this->_height] = Image::imageSize($tempPath);
            } else {
                $this->_width = null;
                $this->_height = null;
            }

            $this->size = filesize($tempPath);
            $this->dateModified = new DateTime('@' . filemtime($tempPath));

            // Delete the temp file
            FileHelper::unlink($tempPath);
        }

        // Clear out the temp location properties
        $this->newLocation = null;
        $this->tempFilePath = null;
    }
}
