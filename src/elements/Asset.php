<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\elements;

use Craft;
use craft\app\base\Element;
use craft\app\base\ElementInterface;
use craft\app\base\Volume;
use craft\app\elements\actions\CopyReferenceTag;
use craft\app\elements\actions\DeleteAssets;
use craft\app\elements\actions\DownloadAssetFile;
use craft\app\elements\actions\Edit;
use craft\app\elements\actions\RenameFile;
use craft\app\elements\actions\ReplaceFile;
use craft\app\elements\actions\View;
use craft\app\elements\db\AssetQuery;
use craft\app\fields\Assets;
use craft\app\helpers\Html;
use craft\app\helpers\Image;
use craft\app\helpers\Io;
use craft\app\helpers\Template;
use craft\app\helpers\Url;
use craft\app\models\VolumeFolder;
use craft\app\records\Asset as AssetRecord;
use craft\app\validators\DateTimeValidator;
use craft\app\validators\UniqueValidator;
use Exception;
use yii\base\ErrorHandler;
use yii\base\InvalidCallException;
use yii\base\UnknownPropertyException;

/**
 * Asset represents an asset element.
 *
 * @property boolean $hasThumb Whether the file has a thumbnail
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Asset extends Element
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName()
    {
        return Craft::t('app', 'Asset');
    }

    /**
     * @inheritdoc
     */
    public static function hasContent()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasTitles()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function isLocalized()
    {
        return true;
    }

    /**
     * @inheritdoc
     *
     * @return AssetQuery The newly created [[AssetQuery]] instance.
     */
    public static function find()
    {
        return new AssetQuery(get_called_class());
    }

    /**
     * @inheritdoc
     */
    public static function getSources($context = null)
    {
        if ($context == 'index') {
            $sourceIds = Craft::$app->getVolumes()->getViewableVolumeIds();
        } else {
            $sourceIds = Craft::$app->getVolumes()->getAllVolumeIds();
        }

        $additionalCriteria = $context == 'settings' ? ['parentId' => ':empty:'] : [];
        $tree = Craft::$app->getAssets()->getFolderTreeByVolumeIds($sourceIds, $additionalCriteria);
        $sources = static::_assembleSourceList($tree, $context != 'settings');

        // Allow plugins to modify the sources
        Craft::$app->getPlugins()->call(
            'modifyAssetSources',
            [&$sources, $context]
        );

        return $sources;
    }

    /**
     * @inheritdoc
     */
    public static function getSourceByKey($key, $context = null)
    {
        if (preg_match('/folder:(\d+)(:single)?/', $key, $matches)) {
            $folder = Craft::$app->getAssets()->getFolderById($matches[1]);

            if ($folder) {
                return static::_assembleSourceInfoForFolder(
                    $folder,
                    empty($matches[2])
                );
            }
        }

        return parent::getSourceByKey($key, $context);
    }

    /**
     * @inheritdoc
     */
    public static function getAvailableActions($source = null)
    {
        $actions = [];

        if (preg_match('/^folder:(\d+)$/', $source, $matches)) {
            $folderId = $matches[1];

            $folder = Craft::$app->getAssets()->getFolderById($folderId);
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

            // Rename File
            if (
                Craft::$app->getAssets()->canUserPerformAction(
                    $folderId,
                    'removeFromVolume'
                ) &&
                Craft::$app->getAssets()->canUserPerformAction(
                    $folderId,
                    'uploadToVolume'
                )
            ) {
                $actions[] = RenameFile::class;
            }

            // Replace File
            if (Craft::$app->getAssets()->canUserPerformAction(
                $folderId,
                'uploadToVolume'
            )
            ) {
                $actions[] = ReplaceFile::class;
            }

            // Copy Reference Tag
            $actions[] = Craft::$app->getElements()->createAction(
                [
                    'type' => CopyReferenceTag::class,
                    'elementType' => Asset::class,
                ]
            );

            // Delete
            if (Craft::$app->getAssets()->canUserPerformAction(
                $folderId,
                'removeFromVolume'
            )
            ) {
                $actions[] = DeleteAssets::class;
            }
        }

        // Allow plugins to add additional actions
        $allPluginActions = Craft::$app->getPlugins()->call(
            'addAssetActions',
            [$source],
            true
        );

        foreach ($allPluginActions as $pluginActions) {
            $actions = array_merge($actions, $pluginActions);
        }

        return $actions;
    }

    /**
     * @inheritdoc
     */
    public static function defineSearchableAttributes()
    {
        return ['filename', 'extension', 'kind'];
    }

    /**
     * @inheritdoc
     */
    public static function defineSortableAttributes()
    {
        $attributes = [
            'title' => Craft::t('app', 'Title'),
            'filename' => Craft::t('app', 'Filename'),
            'size' => Craft::t('app', 'File Size'),
            'dateModified' => Craft::t('app', 'File Modification Date'),
            'elements.dateCreated' => Craft::t('app', 'Date Uploaded'),
            'elements.dateUpdated' => Craft::t('app', 'Date Updated'),
        ];

        // Allow plugins to modify the attributes
        Craft::$app->getPlugins()->call(
            'modifyAssetSortableAttributes',
            [&$attributes]
        );

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public static function defineAvailableTableAttributes()
    {
        $attributes = [
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

        // Allow plugins to modify the attributes
        $pluginAttributes = Craft::$app->getPlugins()->call('defineAdditionalAssetTableAttributes', [], true);

        foreach ($pluginAttributes as $thisPluginAttributes) {
            $attributes = array_merge($attributes, $thisPluginAttributes);
        }

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultTableAttributes($source = null)
    {
        $attributes = ['filename', 'size', 'dateModified'];

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public static function getTableAttributeHtml(ElementInterface $element, $attribute)
    {
        /** @var Asset $element */
        // First give plugins a chance to set this
        $pluginAttributeHtml = Craft::$app->getPlugins()->callFirst(
            'getAssetTableAttributeHtml',
            [$element, $attribute],
            true
        );

        if ($pluginAttributeHtml !== null) {
            return $pluginAttributeHtml;
        }

        switch ($attribute) {
            case 'filename': {
                return Html::encodeParams(
                    '<span style="word-break: break-word;">{filename}</span>',
                    [
                        'filename' => $element->filename,
                    ]
                );
            }

            case 'kind': {
                return Io::getFileKindLabel($element->kind);
            }

            case 'size': {
                if ($element->size) {
                    return Craft::$app->getFormatter()->asShortSize(
                        $element->size
                    );
                } else {
                    return '';
                }
            }

            case 'imageSize': {
                if (($width = $element->getWidth()) && ($height = $element->getHeight())) {
                    return "{$width} × {$height}";
                }

                return '';
            }

            case 'width':
            case 'height': {
                $size = $element->$attribute;

                return ($size ? $size.'px' : '');
            }

            default: {
                return parent::getTableAttributeHtml($element, $attribute);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public static function getEditorHtml(ElementInterface $element)
    {
        /** @var Asset $element */
        $html = Craft::$app->getView()->renderTemplateMacro(
            '_includes/forms',
            'textField',
            [
                [
                    'label' => Craft::t('app', 'Filename'),
                    'id' => 'filename',
                    'name' => 'filename',
                    'value' => $element->filename,
                    'errors' => $element->getErrors('filename'),
                    'first' => true,
                    'required' => true,
                    'class' => 'renameHelper text'
                ]
            ]
        );

        $html .= Craft::$app->getView()->renderTemplateMacro(
            '_includes/forms',
            'textField',
            [
                [
                    'label' => Craft::t('app', 'Title'),
                    'siteId' => $element->siteId,
                    'id' => 'title',
                    'name' => 'title',
                    'value' => $element->title,
                    'errors' => $element->getErrors('title'),
                    'required' => true
                ]
            ]
        );

        $html .= parent::getEditorHtml($element);

        return $html;
    }

    /**
     * @inheritdoc
     */
    public static function saveElement(ElementInterface $element, $params)
    {
        /** @var Asset $element */
        // Is the filename changing?
        if (!empty($params['filename']) && $params['filename'] != $element->filename) {
            // Validate the content before we do anything drastic
            if (!Craft::$app->getContent()->validateContent($element)) {
                return false;
            }

            $oldFilename = $element->filename;
            $newFilename = $params['filename'];

            // Rename the file
            try {
                Craft::$app->getAssets()->renameAsset($element, $newFilename);
            } catch (Exception $exception) {
                $element->addError('filename', $exception->getMessage());

                return false;
            }
            // TODO illegal filenmes?
        } else {
            $newFilename = null;
        }

        $success = parent::saveElement($element, $params);

        if (!$success && $newFilename) {
            // Better rename it back
            /** @noinspection PhpUndefinedVariableInspection */
            Craft::$app->getAssets()->renameAsset($element, $oldFilename);
        }

        return $success;
    }

    /**
     * @inheritdoc
     *
     * @param string $sourceKey
     *
     * @return array
     */
    protected static function getTableAttributesForSource($sourceKey)
    {
        // Make sure it's a folder
        if (strncmp($sourceKey, 'folder:', 7) === 0) {
            $assetsService = Craft::$app->getAssets();
            $folder = $assetsService->getFolderById(substr($sourceKey, 7));

            // Is it a nested folder?
            if ($folder && $folder->parentId) {
                // Get the root folder in that source
                $rootFolder = $assetsService->getRootFolderByVolumeId($folder->volumeId);

                if ($rootFolder) {
                    // Use the root folder's source key
                    $sourceKey = 'folder:'.$rootFolder->id;
                }
            }
        }

        return parent::getTableAttributesForSource($sourceKey);
    }

    /**
     * Transforms an asset folder tree into a source list.
     *
     * @param array   $folders
     * @param boolean $includeNestedFolders
     *
     * @return array
     */
    private static function _assembleSourceList($folders, $includeNestedFolders = true)
    {
        $sources = [];

        foreach ($folders as $folder) {
            $sources['folder:'.$folder->id] = static::_assembleSourceInfoForFolder(
                $folder,
                $includeNestedFolders
            );
        }

        return $sources;
    }

    /**
     * Transforms an VolumeFolderModel into a source info array.
     *
     * @param VolumeFolder $folder
     * @param boolean      $includeNestedFolders
     *
     * @return array
     */
    private static function _assembleSourceInfoForFolder(VolumeFolder $folder, $includeNestedFolders = true)
    {
        $source = [
            'label' => ($folder->parentId ? $folder->name : Craft::t('site', $folder->name)),
            'hasThumbs' => true,
            'criteria' => ['folderId' => $folder->id],
            'data' => [
                'upload' => is_null(
                    $folder->volumeId
                ) ? true : Craft::$app->getAssets()->canUserPerformAction(
                    $folder->id,
                    'uploadToVolume'
                )
            ]
        ];

        if ($includeNestedFolders) {
            $source['nested'] = static::_assembleSourceList(
                $folder->getChildren(),
                true
            );
        }

        return $source;
    }

    // Properties
    // =========================================================================

    /**
     * @var integer Source ID
     */
    public $volumeId;

    /**
     * @var integer Folder ID
     */
    public $folderId;

    /**
     * @var string Folder path
     */
    public $folderPath;

    /**
     * @var string Filename
     */
    public $filename;

    /**
     * @var string Kind
     */
    public $kind;

    /**
     * @var integer Width
     */
    public $width;

    /**
     * @var integer Height
     */
    public $height;

    /**
     * @var integer Size
     */
    public $size;

    /**
     * @var \DateTime Date modified
     */
    public $dateModified;

    /**
     * @var string The new file path
     */
    public $newFilePath;

    /**
     * @var boolean Whether the file is currently being indexed
     */
    public $indexInProgress;

    /**
     * @var
     */
    private $_transform;

    /**
     * @var string
     */
    private $_transformSource = '';

    /**
     * @var Volume
     */
    private $_volume = null;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    /** @noinspection PhpInconsistentReturnPointsInspection */
    public function __toString()
    {
        try {
            if (isset($this->_transform)) {
                return $this->getUrl();
            }

            return parent::__toString();
        } catch (Exception $e) {
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
     * @return boolean Whether the property is set
     */
    public function __isset($name)
    {
        if (parent::__isset($name) || Craft::$app->getAssetTransforms()->getTransformByHandle($name)
        ) {
            return true;
        }

        return false;
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
    public function datetimeAttributes()
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
        $rules[] = [['volumeId', 'folderId', 'width', 'height', 'size'], 'number', 'integerOnly' => true];
        $rules[] = [['dateModified'], DateTimeValidator::class];
        $rules[] = [['filename', 'kind'], 'required'];
        $rules[] = [['kind'], 'string', 'max' => 50];

        $rules[] = [
            ['filename'],
            UniqueValidator::class,
            'targetClass' => AssetRecord::class,
            'targetAttribute' => ['filename', 'folderId'],
            'comboNotUnique' => Craft::t('yii', '{attribute} "{value}" has already been taken.'),
        ];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getFieldLayout()
    {
        $volume = $this->getVolume();

        if ($volume->id) {
            return $volume->getFieldLayout();
        }

        $folder = $this->getFolder();

        if (preg_match('/field_([0-9]+)/', $folder->name, $matches)) {
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
    public function getIsEditable()
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
        if ($this->kind == 'image' && $this->getHasUrls()) {
            $img = '<img src="'.$this->getUrl().'" width="'.$this->getWidth().'" height="'.$this->getHeight().'" alt="'.Html::encode($this->title).'" />';

            return Template::getRaw($img);
        }

        return null;
    }

    /**
     * @return VolumeFolder|null
     */
    public function getFolder()
    {
        return Craft::$app->getAssets()->getFolderById($this->folderId);
    }

    /**
     * @return Volume|null
     */
    public function getVolume()
    {
        if (is_null($this->_volume)) {
            $this->_volume = Craft::$app->getVolumes()->getVolumeById($this->volumeId);
        }

        return $this->_volume;
    }

    /**
     * Sets the transform.
     *
     * @param string|array|null $transform The transform that should be applied, if any. Can either be the handle of a named transform, or an array that defines the transform settings.
     *
     * @return Asset
     */
    public function setTransform($transform)
    {
        $this->_transform = Craft::$app->getAssetTransforms()->normalizeTransform($transform);

        return $this;
    }

    /**
     * Returns the URL to the file.
     *
     * @param string|array|null $transform The transform that should be applied, if any. Can either be the handle of a named transform, or an array that defines the transform settings.
     *
     * @return mixed
     */
    public function getUrl($transform = null)
    {
        if (!$this->getHasUrls()) {
            return false;
        }

        if (is_array($transform)) {
            if (isset($transform['width'])) {
                $transform['width'] = round($transform['width']);
            }
            if (isset($transform['height'])) {
                $transform['height'] = round($transform['height']);
            }
        }

        if ($transform === null && isset($this->_transform)) {
            $transform = $this->_transform;
        }

        return Craft::$app->getAssets()->getUrlForAsset($this, $transform);
    }

    /**
     * @inheritdoc
     */
    public function getThumbUrl($size = 125)
    {
        if ($this->getHasThumb()) {
            return Url::getResourceUrl(
                'resized/'.$this->id.'/'.$size,
                [
                    Craft::$app->getResources()->dateParam => $this->dateModified->getTimestamp()
                ]
            );
        } else {
            return Url::getResourceUrl('icons/'.$this->getExtension());
        }
    }

    /**
     * Returns whether the file has a thumbnail.
     *
     * @return boolean
     */
    public function getHasThumb()
    {
        if ($this->kind == 'image') {
            if ($this->getHeight() && $this->getWidth()) {
                // Gd doesn't process bitmaps or SVGs
                if (in_array(
                        $this->getExtension(),
                        ['svg', 'bmp']
                    ) && Craft::$app->getImages()->getIsGd()
                ) {
                    return false;
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Get the file extension.
     *
     * @return mixed
     */
    public function getExtension()
    {
        return Io::getExtension($this->filename);
    }

    /**
     * @return string
     */
    public function getMimeType()
    {
        return Io::getMimeType($this->filename);
    }

    /**
     * Get image height.
     *
     * @param string|array|null $transform The transform that should be applied, if any. Can either be the handle of a named transform, or an array that defines the transform settings.
     *
     * @return boolean|float|mixed
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
     * @return boolean|float|mixed
     */
    public function getWidth($transform = null)
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
    public function getTransformSource()
    {
        if (!$this->_transformSource) {
            Craft::$app->getAssetTransforms()->getLocalImageSource($this);
        }

        return $this->_transformSource;
    }

    /**
     * Set a source to use for transforms for this Assets File.
     *
     * @param $uri
     */
    public function setTransformSource($uri)
    {
        $this->_transformSource = $uri;
    }

    /**
     * Get a file's uri path in the source.
     *
     * @param string $filename Filename to use. If not specified, the file's filename will be used.
     *
     * @return string
     */
    public function getUri($filename = null)
    {
        return $this->folderPath.($filename ?: $this->filename);
    }

    /**
     * Return the path where the source for this Asset's transforms should be.
     *
     * @return string
     */
    public function getImageTransformSourcePath()
    {
        $volume = Craft::$app->getVolumes()->getVolumeById($this->volumeId);

        if ($volume::isLocal()) {
            return $volume->getRootPath().'/'.$this->getUri();
        }

        return Craft::$app->getPath()->getAssetsImageSourcePath().'/'.$this->id.'.'.$this->getExtension();
    }

    /**
     * Get a temporary copy of the actual file.
     *
     * @return string
     */
    public function getCopyOfFile()
    {
        $copyPath = Io::getTempFilePath($this->getExtension());
        $this->getVolume()->saveFileLocally($this->getUri(), $copyPath);

        return $copyPath;
    }

    /**
     * Return whether the Asset has a URL.
     *
     * @return bool
     */
    public function getHasUrls()
    {
        $volume = $this->getVolume();

        return $volume && $volume->hasUrls;
    }

    // Private Methods
    // =========================================================================

    /**
     * Return a dimension of the image.
     *
     * @param $dimension 'height' or 'width'
     * @param $transform
     *
     * @return null|float|mixed
     */
    private function _getDimension($dimension, $transform)
    {
        if ($this->kind != 'image') {
            return null;
        }

        if ($transform === null && isset($this->_transform)) {
            $transform = $this->_transform;
        }

        if (!$transform) {
            return $this->$dimension;
        }

        $transform = Craft::$app->getAssetTransforms()->normalizeTransform(
            $transform
        );

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
        if ($transform->mode == 'fit') {
            $factor = max($this->width / $dimensions['width'], $this->height / $dimensions['height']);
            $dimensions['width'] = (int)round($this->width / $factor);
            $dimensions['height'] = (int)round($this->height / $factor);
        }

        return $dimensions[$dimension];
    }
}
