<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craft\elements;

use Craft;
use craft\base\Element;
use craft\base\LocalVolumeInterface;
use craft\base\Volume;
use craft\base\VolumeInterface;
use craft\elements\actions\CopyReferenceTag;
use craft\elements\actions\DeleteAssets;
use craft\elements\actions\DownloadAssetFile;
use craft\elements\actions\Edit;
use craft\elements\actions\EditImage;
use craft\elements\actions\RenameFile;
use craft\elements\actions\ReplaceFile;
use craft\elements\actions\View;
use craft\elements\db\AssetQuery;
use craft\elements\db\ElementQueryInterface;
use craft\errors\AssetTransformException;
use craft\errors\FileException;
use craft\events\AssetEvent;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\FileHelper;
use craft\helpers\Html;
use craft\helpers\Image;
use craft\helpers\StringHelper;
use craft\helpers\Template;
use craft\models\AssetTransform;
use craft\models\VolumeFolder;
use craft\records\Asset as AssetRecord;
use craft\validators\AssetLocationValidator;
use craft\validators\DateTimeValidator;
use craft\volumes\Temp;
use DateTime;
use yii\base\ErrorHandler;
use yii\base\Exception;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\base\UnknownPropertyException;

/**
 * Asset represents an asset element.
 *
 * @property bool           $hasThumb Whether the file has a thumbnail
 * @property int|float|null $height   the image height
 * @property int|float|null $width    the image width
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Asset extends Element
{
    // Constants
    // =========================================================================

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

    // Static
    // =========================================================================

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
     *
     * @return AssetQuery The newly created [[AssetQuery]] instance.
     */
    public static function find(): ElementQueryInterface
    {
        return new AssetQuery(static::class);
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

        $sourceList = self::_assembleSourceList($tree, $context !== 'settings');

        // Add the customized temporary upload source
        if ($context !== 'settings') {
            $temporaryUploadFolder = Craft::$app->getAssets()->getCurrentUserTemporaryUploadFolder();
            $temporaryUploadFolder->name = Craft::t('app', 'Temporary Uploads');
            $sourceList[] = self::_assembleSourceInfoForFolder($temporaryUploadFolder, false);
        }

        return $sourceList;
    }

    /**
     * @inheritdoc
     */
    protected static function defineActions(string $source = null): array
    {
        $actions = [];

        if (preg_match('/^folder:(\d+)/', $source, $matches)) {
            $folderId = $matches[1];

            $folder = Craft::$app->getAssets()->getFolderById($folderId);
            /** @var Volume $volume */
            $volume = $folder->getVolume();

            // View for public URLs
            if ($volume->hasUrls) {
                $actions[] = Craft::$app->getElements()->createAction(
                    [
                        'type' => View::class,
                        'label' => Craft::t('app', 'View asset'),
                    ]
                );
            }

            // Download
            $actions[] = DownloadAssetFile::class;

            // Edit
            $actions[] = Craft::$app->getElements()->createAction(
                [
                    'type' => Edit::class,
                    'label' => Craft::t('app', 'Edit asset'),
                ]
            );

            $userSessionService = Craft::$app->getUser();
            $canDeleteAndSave = (
                $userSessionService->checkPermission('deleteFilesAndFoldersInVolume:'.$volume->id) &&
                $userSessionService->checkPermission('saveAssetInVolume:'.$volume->id)
            );

            // Rename File
            if ($canDeleteAndSave) {
                $actions[] = RenameFile::class;
            }

            // Replace File
            if ($userSessionService->checkPermission('saveAssetInVolume:'.$volume->id)) {
                $actions[] = ReplaceFile::class;
            }

            // Copy Reference Tag
            $actions[] = Craft::$app->getElements()->createAction(
                [
                    'type' => CopyReferenceTag::class,
                    'elementType' => static::class,
                ]
            );

            // Edit Image
            if ($canDeleteAndSave) {
                $actions[] = EditImage::class;
            }

            // Delete
            if ($userSessionService->checkPermission('deleteFilesAndFoldersInVolume:'.$volume->id)) {
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
            'dateModified' => Craft::t('app', 'File Modification Date'),
            'elements.dateCreated' => Craft::t('app', 'Date Uploaded'),
            'elements.dateUpdated' => Craft::t('app', 'Date Updated'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        return [
            'title' => ['label' => Craft::t('app', 'Title')],
            'filename' => ['label' => Craft::t('app', 'Filename')],
            'size' => ['label' => Craft::t('app', 'File Size')],
            'kind' => ['label' => Craft::t('app', 'File Kind')],
            'imageSize' => ['label' => Craft::t('app', 'Image Size')],
            'width' => ['label' => Craft::t('app', 'Image Width')],
            'height' => ['label' => Craft::t('app', 'Image Height')],
            'id' => ['label' => Craft::t('app', 'ID')],
            'dateModified' => ['label' => Craft::t('app', 'File Modified Date')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
        ];
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
        ];
    }

    /**
     * Transforms an asset folder tree into a source list.
     *
     * @param array $folders
     * @param bool  $includeNestedFolders
     *
     * @return array
     */
    private static function _assembleSourceList(array $folders, bool $includeNestedFolders = true): array
    {
        $sources = [];

        foreach ($folders as $folder) {
            $sources[] = self::_assembleSourceInfoForFolder($folder, $includeNestedFolders);
        }

        return $sources;
    }

    /**
     * Transforms an VolumeFolderModel into a source info array.
     *
     * @param VolumeFolder $folder
     * @param bool         $includeNestedFolders
     *
     * @return array
     */
    private static function _assembleSourceInfoForFolder(VolumeFolder $folder, bool $includeNestedFolders = true): array
    {
        $source = [
            'key' => 'folder:'.$folder->id,
            'label' => $folder->parentId ? $folder->name : Craft::t('site', $folder->name),
            'hasThumbs' => true,
            'criteria' => ['folderId' => $folder->id],
            'data' => [
                'upload' => $folder->volumeId === null ? true : Craft::$app->getUser()->checkPermission('saveAssetInVolume:'.$folder->volumeId)
            ]
        ];

        if ($includeNestedFolders) {
            $source['nested'] = self::_assembleSourceList(
                $folder->getChildren(),
                true
            );
        }

        return $source;
    }

    // Properties
    // =========================================================================

    /**
     * @var int|null Source ID
     */
    public $volumeId;

    /**
     * @var int|null Folder ID
     */
    public $folderId;

    /**
     * @var string|null Folder path
     */
    public $folderPath;

    /**
     * @var string|null Filename
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
     * @var string|null Focal point
     */
    public $focalPoint;

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
     * @var bool Whether the associated file should be preserved if the asset record is deleted.
     */
    public $keepFileOnDelete = false;

    /**
     * @var int|float|null Width
     */
    private $_width;

    /**
     * @var int|float|null Height
     */
    private $_height;

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

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    /** @noinspection PhpInconsistentReturnPointsInspection */
    public function __toString()
    {
        try {
            if ($this->_transform !== null) {
                return (string)$this->getUrl();
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
     *
     * - a magic property supported by [[Element::__isset()]]
     * - an image transform handle
     *
     * @param string $name The property name
     *
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
     *
     * - a magic property supported by [[Element::__get()]]
     * - an image transform handle
     *
     * @param string $name The property name
     *
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
    public function rules()
    {
        $rules = parent::rules();

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
     */
    public function getIsEditable(): bool
    {
        return Craft::$app->getUser()->checkPermission(
            'saveAssetInVolume:'.$this->volumeId
        );
    }

    /**
     * Returns an `<img>` tag based on this asset.
     *
     * @return \Twig_Markup|null
     */
    public function getImg()
    {
        if ($this->kind === 'image' && $this->getHasUrls()) {
            $img = '<img src="'.$this->getUrl().'" width="'.$this->getWidth().'" height="'.$this->getHeight().'" alt="'.Html::encode($this->title).'" />';

            return Template::raw($img);
        }

        return null;
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
            throw new InvalidConfigException('Invalid folder ID: '.$this->folderId);
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

        if ($this->volumeId === null) {
            return new Temp();
        }

        if (($volume = Craft::$app->getVolumes()->getVolumeById($this->volumeId)) === null) {
            throw new InvalidConfigException('Invalid volume ID: '.$this->volumeId);
        }

        return $this->_volume = $volume;
    }

    /**
     * Sets the transform.
     *
     * @param AssetTransform|string|array|null $transform The transform that should be applied, if any. Can either be the handle of a named transform, or an array that defines the transform settings.
     *
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
     * @param string|array|null $transform The transform that should be applied, if any. Can either be the handle of a named transform, or an array that defines the transform settings.
     *
     * @return string|null
     */
    public function getUrl($transform = null)
    {
        if (!$this->getHasUrls()) {
            return null;
        }

        if (is_array($transform)) {
            if (isset($transform['width'])) {
                $transform['width'] = round($transform['width']);
            }
            if (isset($transform['height'])) {
                $transform['height'] = round($transform['height']);
            }
        }

        if ($transform === null && $this->_transform !== null) {
            $transform = $this->_transform;
        }

        return Craft::$app->getAssets()->getAssetUrl($this, $transform);
    }

    /**
     * @inheritdoc
     */
    public function getThumbUrl(int $size)
    {
        return Craft::$app->getAssets()->getThumbUrl($this, $size, false);
    }

    /**
     * Returns the file name, with or without the extension.
     *
     * @param bool $withExtension
     *
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
     *
     * @return int|float|null
     */

    public function getHeight($transform = null)
    {
        return $this->_getDimension('height', $transform);
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
     *
     * @return int|float|null
     */
    public function getWidth($transform = null)
    {
        return $this->_getDimension('width', $transform);
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
     * Get a file's uri path in the source.
     *
     * @param string|null $filename Filename to use. If not specified, the file's filename will be used.
     *
     * @return string
     */
    public function getUri(string $filename = null): string
    {
        return $this->folderPath.($filename ?: $this->filename);
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
            return FileHelper::normalizePath($volume->getRootPath().DIRECTORY_SEPARATOR.$this->getUri());
        }

        return Craft::$app->getPath()->getAssetSourcesPath().DIRECTORY_SEPARATOR.$this->id.'.'.$this->getExtension();
    }

    /**
     * Get a temporary copy of the actual file.
     *
     * @return string
     */
    public function getCopyOfFile(): string
    {
        $tempFilename = uniqid(pathinfo($this->filename, PATHINFO_FILENAME), true).'.'.$this->getExtension();
        $tempPath = Craft::$app->getPath()->getTempPath().DIRECTORY_SEPARATOR.$tempFilename;
        $this->getVolume()->saveFileLocally($this->getUri(), $tempPath);

        return $tempPath;
    }

    /**
     * Get a stream of the actual file.
     *
     * @return resource
     */
    public function getStream()
    {
        return $this->getVolume()->getFileStream($this->getUri());
    }

    /**
     * Return whether the Asset has a URL.
     *
     * @return bool
     */
    public function getHasUrls(): bool
    {
        /** @var Volume $volume */
        $volume = $this->getVolume();

        return $volume && $volume->hasUrls;
    }

    /**
     * Return the Asset's focal point or null if not an image.
     *
     * @return null|array
     */
    public function getFocalPoint()
    {
        if ($this->kind !== 'image') {
            return null;
        }

        if (!empty($this->focalPoint)) {
            $focal = explode(';', $this->focalPoint);
            if (count($focal) === 2) {
                return ['x' => $focal[0], 'y' => $focal[1]];
            }
        }

        return ['x' => 0.5, 'y' => 0.5];
    }

    // Indexes, etc.
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'filename':
                return Html::encodeParams('<span style="word-wrap: break-word;;">{filename}</span>', [
                    'filename' => $this->filename,
                ]);

            case 'kind':
                return AssetsHelper::getFileKindLabel($this->kind);

            case 'size':
                return $this->size ? Craft::$app->getFormatter()->asShortSize($this->size) : '';

            case 'imageSize':
                return (($width = $this->getWidth()) && ($height = $this->getHeight())) ? "{$width} × {$height}" : '';

            case 'width':
            case 'height':
                $size = $this->$attribute;

                return ($size ? $size.'px' : '');
        }

        return parent::tableAttributeHtml($attribute);
    }

    /**
     * @inheritdoc
     */
    public function getEditorHtml(): string
    {
        if (!$this->fieldLayoutId) {
            $this->fieldLayoutId = Craft::$app->getRequest()->getBodyParam('defaultFieldLayoutId');
        }

        $html = Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'textField', [
            [
                'label' => Craft::t('app', 'Filename'),
                'id' => 'newFilename',
                'name' => 'newFilename',
                'value' => $this->filename,
                'errors' => $this->getErrors('newLocation'),
                'first' => true,
                'required' => true,
                'class' => 'renameHelper text'
            ]
        ]);

        $html .= Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'textField', [
            [
                'label' => Craft::t('app', 'Title'),
                'siteId' => $this->siteId,
                'id' => 'title',
                'name' => 'title',
                'value' => $this->title,
                'errors' => $this->getErrors('title'),
                'required' => true
            ]
        ]);

        $html .= parent::getEditorHtml();

        return $html;
    }

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        $attributes = parent::attributes();
        $attributes[] = 'width';
        $attributes[] = 'height';

        return $attributes;
    }

    /**
     * Returns a copy of the asset with the given transform applied to it.
     *
     * @param AssetTransform|string|array|null $transform
     *
     * @return Asset
     * @throws AssetTransformException if $transform is an invalid transform handle
     */
    public function copyWithTransform($transform): Asset
    {
        // Duplicate this model and set it to that transform
        $model = new self();

        // Can't just use attributes() here because we'll get thrown into an infinite loop.
        foreach ($this->attributes() as $attributeName) {
            $model->$attributeName = $this->$attributeName;
        }

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
            $this->newLocation = "{folder:{$folderId}}{$filename}";
            $this->newFolderId = $this->newFilename = null;
        }

        // Get the (new?) folder ID
        if ($this->newLocation !== null) {
            list($folderId) = AssetsHelper::parseFileLocation($this->newLocation);
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
        if (!$this->id && (!$this->title || $this->title === Craft::t('app', 'New Element'))) {
            $this->title = StringHelper::toTitleCase(pathinfo($this->filename, PATHINFO_FILENAME));
        }

        // Set the field layout
        /** @var Volume $volume */
        $volume = Craft::$app->getAssets()->getFolderById($folderId)->getVolume();
        $this->fieldLayoutId = $volume->fieldLayoutId;

        return parent::beforeSave($isNew);
    }

    /**
     * @inheritdoc
     * @throws Exception if the asset isn't new but doesn't have a row in the `assets` table for some reason
     */
    public function afterSave(bool $isNew)
    {
        // If this is just an element being propagated, there's absolutely no need for re-saving this.
        if (!$this->propagating) {
            // Relocate the file?
            if ($this->newLocation !== null || $this->tempFilePath !== null) {
                $this->_relocateFile();
            }

            // Get the asset record
            if (!$isNew) {
                $record = AssetRecord::findOne($this->id);

                if (!$record) {
                    throw new Exception('Invalid asset ID: '.$this->id);
                }
            } else {
                $record = new AssetRecord();
                $record->id = $this->id;
            }


            $record->filename = $this->filename;
            $record->volumeId = $this->volumeId;
            $record->folderId = $this->folderId;
            $record->kind = $this->kind;
            $record->size = $this->size;
            $record->focalPoint = $this->focalPoint;
            $record->width = $this->_width;
            $record->height = $this->_height;
            $record->dateModified = $this->dateModified;
            $record->save(false);
        }

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function afterDelete()
    {
        if (!$this->keepFileOnDelete) {
            $this->getVolume()->deleteFile($this->getUri());
        }

        Craft::$app->getAssetTransforms()->deleteAllTransformData($this);
        parent::afterDelete();
    }

    // Private Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function htmlAttributes(string $context): array
    {
        $attributes = [];

        if ($context === 'index') {
            // Eligible for the image editor?
            $ext = $this->getExtension();
            if (strcasecmp($ext, 'svg') !== 0 && Image::canManipulateAsImage($ext)) {
                $attributes['data-editable-image'] = null;
            }
        }

        return $attributes;
    }

    // Private Methods
    // =========================================================================

    /**
     * Return a dimension of the image.
     *
     * @param string                           $dimension 'height' or 'width'
     * @param AssetTransform|string|array|null $transform
     *
     * @return int|float|null
     */
    private function _getDimension(string $dimension, $transform)
    {
        if ($this->kind !== 'image') {
            return null;
        }

        $transform = $transform ?? $this->_transform;

        if ($transform !== null && !Image::canManipulateAsImage($this->getExtension())) {
            $transform = null;
        }

        if ($transform === null) {
            return $this->{'_'.$dimension};
        }

        $transform = Craft::$app->getAssetTransforms()->normalizeTransform($transform);

        $dimensions = [
            'width' => $transform->width,
            'height' => $transform->height
        ];

        if (!$transform->width || !$transform->height) {
            // Fill in the blank
            $dimensionArray = Image::calculateMissingDimension($dimensions['width'], $dimensions['height'], $this->_width, $this->_height);
            $dimensions['width'] = (int)$dimensionArray[0];
            $dimensions['height'] = (int)$dimensionArray[1];
        }

        // Special case for 'fit' since that's the only one whose dimensions vary from the transform dimensions
        if ($transform->mode === 'fit') {
            $factor = max($this->_width / $dimensions['width'], $this->_height / $dimensions['height']);
            $dimensions['width'] = (int)round($this->_width / $factor);
            $dimensions['height'] = (int)round($this->_height / $factor);
        }

        return $dimensions[$dimension];
    }

    /**
     * Relocates the file after the element has been saved.
     *
     * @return void
     * @throws FileException if the file is being moved but cannot be read
     */
    private function _relocateFile()
    {
        $assetsService = Craft::$app->getAssets();

        // Get the (new?) folder ID & filename
        if ($this->newLocation !== null) {
            list($folderId, $filename) = AssetsHelper::parseFileLocation($this->newLocation);
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

        $oldPath = $this->folderId ? $this->getUri() : null;
        $newPath = ($newFolder->path ? rtrim($newFolder->path, '/').'/' : '').$filename;

        // Is this just a simple move/rename within the same volume?
        if ($this->tempFilePath === null && $oldFolder !== null && $oldFolder->volumeId == $newFolder->volumeId) {
            $oldVolume->renameFile($oldPath, $newPath);
        } else {
            // Get the temp path
            if ($this->tempFilePath !== null) {
                $tempPath = $this->tempFilePath;
            } else {
                $tempFilename = uniqid(pathinfo($filename, PATHINFO_FILENAME), true).'.'.pathinfo($filename, PATHINFO_EXTENSION);
                $tempPath = Craft::$app->getPath()->getTempPath().DIRECTORY_SEPARATOR.$tempFilename;
                $oldVolume->saveFileLocally($oldPath, $tempPath);
            }

            // Try to open a file stream
            if (($stream = fopen($tempPath, 'rb')) === false) {
                FileHelper::removeFile($tempPath);
                throw new FileException(Craft::t('app', 'Could not open file for streaming at {path}', ['path' => $tempPath]));
            }

            if ($this->folderId) {
                // Delete the old file
                $oldVolume->deleteFile($oldPath);
            }

            // Upload the file to the new location
            $newVolume->createFileByStream($newPath, $stream, []);

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
        $this->volumeId = $newFolder->volumeId;
        $this->folderId = $folderId;
        $this->folderPath = $newFolder->path;
        $this->filename = $filename;

        // If there was a new file involved, update file data.
        if ($tempPath) {
            $this->kind = AssetsHelper::getFileKindByExtension($filename);

            if ($this->kind === 'image') {
                list ($this->_width, $this->_height) = Image::imageSize($tempPath);
            } else {
                $this->_width = null;
                $this->_height = null;
            }

            $this->size = filesize($tempPath);
            $this->dateModified = new DateTime('@'.filemtime($tempPath));

            // Delete the temp file
            FileHelper::removeFile($tempPath);
        }

        // Clear out the temp location properties
        $this->newLocation = null;
        $this->tempFilePath = null;
    }
}
