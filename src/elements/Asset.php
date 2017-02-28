<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
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
use craft\fields\Assets;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\FileHelper;
use craft\helpers\Html;
use craft\helpers\Image;
use craft\helpers\StringHelper;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\models\AssetTransform;
use craft\models\VolumeFolder;
use craft\records\Asset as AssetRecord;
use craft\validators\AssetFilenameValidator;
use craft\validators\DateTimeValidator;
use yii\base\ErrorHandler;
use yii\base\Exception;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\base\UnknownPropertyException;

/**
 * Asset represents an asset element.
 *
 * @property bool $hasThumb Whether the file has a thumbnail
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Asset extends Element
{
    // Constants
    // =========================================================================

    // Validation scenarios
    // -------------------------------------------------------------------------

    const SCENARIO_FILENAME = 'filename';

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
        return new AssetQuery(get_called_class());
    }

    /**
     * @inheritdoc
     */
    protected static function defineSources(string $context = null): array
    {
        if ($context === 'index') {
            $sourceIds = Craft::$app->getVolumes()->getViewableVolumeIds();
        } else {
            $sourceIds = Craft::$app->getVolumes()->getAllVolumeIds();
        }

        $additionalCriteria = $context === 'settings' ? ['parentId' => ':empty:'] : [];
        $tree = Craft::$app->getAssets()->getFolderTreeByVolumeIds($sourceIds, $additionalCriteria);

        return self::_assembleSourceList($tree, $context !== 'settings');
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

            // Rename File
            if (
                $userSessionService->checkPermission('removeFromVolume:'.$volume->id)
                &&
                $userSessionService->checkPermission('uploadToVolume:'.$volume->id)
            ) {
                $actions[] = RenameFile::class;
                $actions[] = EditImage::class;
            }

            // Replace File
            if ($userSessionService->checkPermission('uploadToVolume:'.$volume->id)) {
                $actions[] = ReplaceFile::class;
            }

            // Copy Reference Tag
            $actions[] = Craft::$app->getElements()->createAction(
                [
                    'type' => CopyReferenceTag::class,
                    'elementType' => static::class,
                ]
            );

            // Delete
            if ($userSessionService->checkPermission('removeFromVolume:'.$volume->id)) {
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
                'upload' => $folder->volumeId === null ? true : Craft::$app->getUser()->checkPermission('uploadToVolume:'.$folder->volumeId)
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
     * @var int|null Width
     */
    public $width;

    /**
     * @var int|null Height
     */
    public $height;

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
     * @var string|null New filename
     */
    public $newFilename;

    /**
     * @var string|null The new file path
     */
    public $newFilePath;

    /**
     * @var bool Whether the associated file should be preserved if the asset record is deleted.
     */
    public $keepFileOnDelete = false;

    /**
     * @var bool Whether the file is currently being indexed
     */
    public $indexInProgress = false;

    /**
     * @var
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
        return parent::__isset($name) || Craft::$app->getAssetTransforms()->getTransformByHandle($name);
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
        try {
            return parent::__get($name);
        } catch (UnknownPropertyException $e) {
            // Is $name a transform handle?
            $transform = Craft::$app->getAssetTransforms()->getTransformByHandle($name);

            if ($transform) {
                // Duplicate this model and set it to that transform
                $model = new Asset();

                // Can't just use attributes() here because we'll get thrown into an infinite loop.
                foreach ($this->attributes() as $attributeName) {
                    $model->$attributeName = $this->$attributeName;
                }

                $model->setFieldValues($this->getFieldValues());
                $model->setTransform($transform);

                return $model;
            }

            throw $e;
        }
    }

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $names = parent::datetimeAttributes();
        $names[] = 'dateModified';

        return $names;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();

        if ($this->id !== null && !$this->title) {
            // Don't validate the title
            $key = array_search([['title'], 'required'], $rules, true);
            if ($key !== -1) {
                array_splice($rules, $key, 1);
            }
        }

        $rules[] = [['volumeId', 'folderId', 'width', 'height', 'size'], 'number', 'integerOnly' => true];
        $rules[] = [['dateModified'], DateTimeValidator::class];
        $rules[] = [['filename', 'kind'], 'required'];
        $rules[] = [['kind'], 'string', 'max' => 50];
        $rules[] = [['newFilename'], AssetFilenameValidator::class];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_FILENAME] = ['newFilename'];

        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function getFieldLayout()
    {
        /** @var Volume $volume */
        $volume = $this->getVolume();

        if ($volume->id !== null) {
            return $volume->getFieldLayout();
        }

        $folder = $this->getFolder();

        if (preg_match('/field_(\d+)/', $folder->name, $matches)) {
            $fieldId = $matches[1];
            /** @var Assets $field */
            $field = Craft::$app->getFields()->getFieldById($fieldId);
            $settings = $field->settings;

            if ($settings['useSingleFolder']) {
                $sourceId = $settings['singleUploadLocationSource'];
            } else {
                $sourceId = $settings['defaultUploadLocationSource'];
            }

            $volume = Craft::$app->getVolumes()->getVolumeById($sourceId);

            if ($volume) {
                return $volume->getFieldLayout();
            }
        }


        return null;
    }

    /**
     * @inheritdoc
     */
    public function getIsEditable(): bool
    {
        return Craft::$app->getUser()->checkPermission(
            'uploadToVolume:'.$this->volumeId
        );
    }

    /**
     * Returns an <img> tag based on this asset.
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
            throw new InvalidConfigException('Asset is missing its volume ID');
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
     */
    public function setTransform($transform): Asset
    {
        $this->_transform = Craft::$app->getAssetTransforms()->normalizeTransform($transform);

        return $this;
    }

    /**
     * @inheritdoc
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

        return Craft::$app->getAssets()->getUrlForAsset($this, $transform);
    }

    /**
     * @inheritdoc
     */
    public function getThumbUrl(int $size)
    {
        if ($this->getHasThumb()) {
            return UrlHelper::resourceUrl('resized/'.$this->id.'/'.$size, [
                Craft::$app->getResources()->dateParam => $this->dateModified->getTimestamp()
            ]);
        } else {
            return UrlHelper::resourceUrl('icons/'.$this->getExtension());
        }
    }

    /**
     * Returns whether the file has a thumbnail.
     *
     * @return bool
     */
    public function getHasThumb(): bool
    {
        return (
            $this->kind === 'image' &&
            $this->getHeight() &&
            $this->getWidth() &&
            (!in_array($this->getExtension(), ['svg', 'bmp'], true) || Craft::$app->getImages()->getIsImagick())
        );
    }

    /**
     * Get the file extension.
     *
     * @return mixed
     */
    public function getExtension()
    {
        return pathinfo($this->filename, PATHINFO_EXTENSION);
    }

    /**
     * @return string
     */
    public function getMimeType(): string
    {
        // todo: maybe we should be passing this off to volume types
        // so Local volumes can call FileHelper::getMimeType() (uses magic file instead of ext)
        return FileHelper::getMimeTypeByExtension($this->filename);
    }

    /**
     * Get image height.
     *
     * @param string|array|null $transform The transform that should be applied, if any. Can either be the handle of a named transform, or an array that defines the transform settings.
     *
     * @return bool|float|mixed
     */

    public function getHeight($transform = null)
    {
        if ($transform !== null && !Image::isImageManipulatable(
                $this->getExtension()
            )
        ) {
            $transform = null;
        }

        return $this->_getDimension('height', $transform);
    }

    /**
     * Get image width.
     *
     * @param string|null $transform The optional transform handle for which to get thumbnail.
     *
     * @return bool|float|mixed
     */
    public function getWidth(string $transform = null)
    {
        if ($transform !== null && !Image::isImageManipulatable(
                $this->getExtension()
            )
        ) {
            $transform = null;
        }

        return $this->_getDimension('width', $transform);
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
        $volume = Craft::$app->getVolumes()->getVolumeById($this->volumeId);

        if ($volume instanceof LocalVolumeInterface) {
            return FileHelper::normalizePath($volume->getRootPath().DIRECTORY_SEPARATOR.$this->getUri());
        }

        return Craft::$app->getPath()->getAssetsImageSourcePath().DIRECTORY_SEPARATOR.$this->id.'.'.$this->getExtension();
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

        if ($this->focalPoint) {
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
                /** @noinspection CssInvalidPropertyValue - FP */
                return Html::encodeParams('<span style="word-break: break-word;">{filename}</span>', [
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
        $html = Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'textField', [
            [
                'label' => Craft::t('app', 'Filename'),
                'id' => 'newFilename',
                'name' => 'newFilename',
                'value' => $this->filename,
                'errors' => $this->getErrors('filename'),
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

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     * @throws Exception if reasons
     */
    public function beforeSave(bool $isNew): bool
    {
        if ($isNew && (!$this->title || $this->title === Craft::t('app', 'New Element'))) {
            // Give it a default title based on the file name
            $this->title = StringHelper::toTitleCase(pathinfo($this->filename, PATHINFO_FILENAME));
        }

        return parent::beforeSave($isNew);
    }

    /**
     * @inheritdoc
     * @throws Exception if reasons
     */
    public function afterSave(bool $isNew)
    {
        // Get the asset record
        if (!$isNew) {
            $record = AssetRecord::findOne($this->id);

            if (!$record) {
                throw new Exception('Invalid asset ID: '.$this->id);
            }

            if ($this->filename !== $record->filename) {
                throw new Exception('Unable to change an asset’s filename like this.');
            }
        } else {
            $record = new AssetRecord();
            $record->id = $this->id;
            $record->filename = $this->filename;
        }

        $record->volumeId = $this->volumeId;
        $record->folderId = $this->folderId;
        $record->kind = $this->kind;
        $record->size = $this->size;
        $record->focalPoint = $this->focalPoint;
        $record->width = $this->width;
        $record->height = $this->height;
        $record->dateModified = $this->dateModified;
        $record->save(false);

        if ($this->newFilename !== null) {
            if ($this->newFilename === $this->filename) {
                $this->newFilename = null;
            } else {
                Craft::$app->getAssets()->renameFile($this, false);
            }
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
            if (strcasecmp($ext, 'svg') !== 0 && Image::isImageManipulatable($ext)) {
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
     * @return null|float|mixed
     */
    private function _getDimension(string $dimension, $transform)
    {
        if ($this->kind !== 'image') {
            return null;
        }

        if ($transform === null && $this->_transform !== null) {
            $transform = $this->_transform;
        }

        if (!$transform) {
            return $this->$dimension;
        }

        $transform = Craft::$app->getAssetTransforms()->normalizeTransform($transform);

        $dimensions = [
            'width' => $transform->width,
            'height' => $transform->height
        ];

        if (!$transform->width || !$transform->height) {
            // Fill in the blank
            $dimensionArray = Image::calculateMissingDimension($dimensions['width'], $dimensions['height'], $this->width, $this->height);
            $dimensions['width'] = (int)$dimensionArray[0];
            $dimensions['height'] = (int)$dimensionArray[1];
        }

        // Special case for 'fit' since that's the only one whose dimensions vary from the transform dimensions
        if ($transform->mode === 'fit') {
            $factor = max($this->width / $dimensions['width'], $this->height / $dimensions['height']);
            $dimensions['width'] = (int)round($this->width / $factor);
            $dimensions['height'] = (int)round($this->height / $factor);
        }

        return $dimensions[$dimension];
    }
}
