<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\elements;

use Craft;
use craft\app\base\Element;
use craft\app\base\ElementInterface;
use craft\app\base\Volume;
use craft\app\elements\actions\CopyReferenceTag;
use craft\app\elements\actions\DeleteAssets;
use craft\app\elements\actions\Edit;
use craft\app\elements\actions\RenameFile;
use craft\app\elements\actions\ReplaceFile;
use craft\app\elements\actions\View;
use craft\app\elements\db\AssetQuery;
use craft\app\errors\AssetConflictException;
use craft\app\helpers\HtmlHelper;
use craft\app\helpers\ImageHelper;
use craft\app\helpers\IOHelper;
use craft\app\helpers\TemplateHelper;
use craft\app\helpers\UrlHelper;
use craft\app\models\VolumeFolder;
use Exception;
use yii\base\ErrorHandler;
use yii\base\InvalidCallException;
use yii\base\UnknownPropertyException;

/**
 * Asset represents an asset element.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
		if ($context == 'index')
		{
			$sourceIds = Craft::$app->getVolumes()->getViewableVolumeIds();
		}
		else
		{
			$sourceIds = Craft::$app->getVolumes()->getAllVolumeIds();
		}

		$tree = Craft::$app->getAssets()->getFolderTreeByVolumeIds($sourceIds);

		$sources = static::_assembleSourceList($tree);

		// Allow plugins to modify the sources
		Craft::$app->getPlugins()->call('modifyAssetSources', [&$sources, $context]);

		return $sources;
	}

	/**
	 * @inheritdoc
	 */
	public static function getSourceByKey($key, $context = null)
	{
		if (preg_match('/folder:(\d+)(:single)?/', $key, $matches))
		{
			$folder = Craft::$app->getAssets()->getFolderById($matches[1]);

			if ($folder)
			{
				return static::_assembleSourceInfoForFolder($folder, empty($matches[2]));
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

		if (preg_match('/^folder:(\d+)$/', $source, $matches))
		{
			$folderId = $matches[1];

			// View
			$actions[] = Craft::$app->getElements()->createAction([
				'type'  => View::className(),
				'label' => Craft::t('app', 'View asset'),
			]);

			// Edit
			$actions[] = Craft::$app->getElements()->createAction([
				'type'  => Edit::className(),
				'label' => Craft::t('app', 'Edit asset'),
			]);

			// Rename File
			if (
				Craft::$app->getAssets()->canUserPerformAction($folderId, 'removeFromVolume') &&
				Craft::$app->getAssets()->canUserPerformAction($folderId, 'uploadToVolume')
			)
			{
				$actions[] = RenameFile::className();
			}

			// Replace File
			if (Craft::$app->getAssets()->canUserPerformAction($folderId, 'uploadToVolume'))
			{
				$actions[] = ReplaceFile::className();
			}

			// Copy Reference Tag
			$actions[] = Craft::$app->getElements()->createAction([
				'type'        => CopyReferenceTag::className(),
				'elementType' => Asset::className(),
			]);

			// Delete
			if (Craft::$app->getAssets()->canUserPerformAction($folderId, 'removeFromVolume'))
			{
				$actions[] = DeleteAssets::className();
			}
		}

		// Allow plugins to add additional actions
		$allPluginActions = Craft::$app->getPlugins()->call('addAssetActions', [$source], true);

		foreach ($allPluginActions as $pluginActions)
		{
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
			'title'        => Craft::t('app', 'Title'),
			'filename'     => Craft::t('app', 'Filename'),
			'size'         => Craft::t('app', 'Size'),
			'dateModified' => Craft::t('app', 'Date Modified'),
		];

		// Allow plugins to modify the attributes
		Craft::$app->getPlugins()->call('modifyAssetSortableAttributes', [&$attributes]);

		return $attributes;
	}

	/**
	 * @inheritdoc
	 */
	public static function defineTableAttributes($source = null)
	{
		$attributes = [
			'title'        => Craft::t('app', 'Title'),
			'filename'     => Craft::t('app', 'Filename'),
			'size'         => Craft::t('app', 'Size'),
			'dateModified' => Craft::t('app', 'Date Modified'),
		];

		// Allow plugins to modify the attributes
		Craft::$app->getPlugins()->call('modifyAssetTableAttributes', [&$attributes, $source]);

		return $attributes;
	}

	/**
	 * @inheritdoc
	 */
	public static function getTableAttributeHtml(ElementInterface $element, $attribute)
	{
		/** @var Asset $element */
		// First give plugins a chance to set this
		$pluginAttributeHtml = Craft::$app->getPlugins()->callFirst('getAssetTableAttributeHtml', [$element, $attribute], true);

		if ($pluginAttributeHtml !== null)
		{
			return $pluginAttributeHtml;
		}

		switch ($attribute)
		{
			case 'filename':
			{
				return HtmlHelper::encodeParams('<span style="word-break: break-word;">{filename}</span>', [
					'filename' => $element->filename,
				]);
			}

			case 'size':
			{
				if ($element->size)
				{
					return Craft::$app->getFormatter()->asShortSize($element->size);
				}
				else
				{
					return '';
				}
			}

			default:
			{
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
		$html = Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'textField', [
			[
				'label'     => Craft::t('app', 'Filename'),
				'id'        => 'filename',
				'name'      => 'filename',
				'value'     => $element->filename,
				'errors'    => $element->getErrors('filename'),
				'first'     => true,
				'required'  => true
			]
		]);

		$html .= Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'textField', [
			[
				'label'     => Craft::t('app', 'Title'),
				'locale'    => $element->locale,
				'id'        => 'title',
				'name'      => 'title',
				'value'     => $element->title,
				'errors'    => $element->getErrors('title'),
				'required'  => true
			]
		]);

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
		if (!empty($params['filename']) && $params['filename'] != $element->filename)
		{
			// Validate the content before we do anything drastic
			if (!Craft::$app->getContent()->validateContent($element))
			{
				return false;
			}

			$oldFilename = $element->filename;
			$newFilename = $params['filename'];

			// Rename the file
			try
			{
				Craft::$app->getAssets()->renameAsset($element, $newFilename);
			}
			catch (AssetConflictException $exception)
			{
				$element->addError('filename', $exception->getMessage());
				return false;
			}

			// TODO illegal filenmes?
		}
		else
		{
			$newFilename = null;
		}

		$success = parent::saveElement($element, $params);

		if (!$success && $newFilename)
		{
			// Better rename it back
			Craft::$app->getAssets()->renameAsset($element, $oldFilename);
		}

		return $success;
	}

	/**
	 * Transforms an asset folder tree into a source list.
	 *
	 * @param array $folders
	 * @param bool  $includeNestedFolders
	 *
	 * @return array
	 */
	private static function _assembleSourceList($folders, $includeNestedFolders = true)
	{
		$sources = [];

		foreach ($folders as $folder)
		{
			$sources['folder:'.$folder->id] = static::_assembleSourceInfoForFolder($folder, $includeNestedFolders);
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
	private static function _assembleSourceInfoForFolder(VolumeFolder $folder, $includeNestedFolders = true)
	{
		$source = [
			'label'     => ($folder->parentId ? $folder->name : Craft::t('app', $folder->name)),
			'hasThumbs' => true,
			'criteria'  => ['folderId' => $folder->id],
			'data'      => ['upload' => is_null($folder->volumeId) ? true : Craft::$app->getAssets()->canUserPerformAction($folder->id, 'uploadToVolume')]
		];

		if ($includeNestedFolders)
		{
			$source['nested'] = static::_assembleSourceList($folder->getChildren(), true);
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
	 * @var string Filename
	 */
	public $filename;

	/**
	 * @var string Original name
	 */
	public $originalName;

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

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function __toString()
	{
		try
		{
			if (isset($this->_transform))
			{
				return $this->getUrl();
			}
			else
			{
				return parent::__toString();
			}
		}
		catch (Exception $e)
		{
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
	 * @return boolean Whether the property is set
	 */
	public function __isset($name)
	{
		if (parent::__isset($name) || Craft::$app->getAssetTransforms()->getTransformByHandle($name))
		{
			return true;
		}
		else
		{
			return false;
		}
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
	 * @return mixed The property value
	 * @throws UnknownPropertyException if the property is not defined
	 * @throws InvalidCallException if the property is write-only.
	 */
	public function __get($name)
	{
		try
		{
			return parent::__get($name);
		}
		catch (UnknownPropertyException $e)
		{
			// Is $name a transform handle?
			$transform = Craft::$app->getAssetTransforms()->getTransformByHandle($name);

			if ($transform)
			{
				// Duplicate this model and set it to that transform
				$model = new Asset();

				// Can't just use attributes() here because we'll get thrown into an infinite loop.
				foreach ($this->attributes() as $attributeName)
				{
					$model->$attributeName = $this->$attributeName;
				}

				$model->setContent($this->getContent());
				$model->setTransform($transform);

				return $model;
			}
			else
			{
				throw $e;
			}
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

		$rules[] = [['volumeId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true];
		$rules[] = [['folderId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true];
		$rules[] = [['width'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true];
		$rules[] = [['height'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true];
		$rules[] = [['size'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true];
		$rules[] = [['dateModified'], 'craft\\app\\validators\\DateTime'];

		return $rules;
	}

	/**
	 * @inheritdoc
	 */
	public function getFieldLayout()
	{
		$source = $this->getVolume();

		if ($source->id)
		{
			return $source->getFieldLayout();
		}
		else
		{
			$folder = $this->getFolder();

			if (preg_match('/field_([0-9]+)/', $folder->name, $matches))
			{
				$fieldId = $matches[1];
				$field = Craft::$app->getFields()->getFieldById($fieldId);
				$settings = $field->settings;

				if ($settings['useSingleFolder'])
				{
					$sourceId = $settings['singleUploadLocationSource'];
				}
				else
				{
					$sourceId = $settings['defaultUploadLocationSource'];
				}

				$source = Craft::$app->getVolumes()->getVolumeById($sourceId);

				if ($source)
				{
					return $source->getFieldLayout();
				}
			}
		}

		return null;
	}

	/**
	 * @inheritdoc
	 */
	public function isEditable()
	{
		return Craft::$app->getUser()->checkPermission('uploadToVolume:'.$this->volumeId);
	}

	/**
	 * Returns an <img> tag based on this asset.
	 *
	 * @return \Twig_Markup|null
	 */
	public function getImg()
	{
		if ($this->kind == 'image')
		{
			$img = '<img src="'.$this->url.'" width="'.$this->getWidth().'" height="'.$this->getHeight().'" alt="'.HtmlHelper::encode($this->title).'" />';
			return TemplateHelper::getRaw($img);
		}
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
		return Craft::$app->getVolumes()->getVolumeById($this->volumeId);
	}

	/**
	 * Sets the transform.
	 *
	 * @param mixed $transform
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
	 * @param string|null $transform
	 *
	 * @return mixed
	 */
	public function getUrl($transform = null)
	{
		if ($transform === null && isset($this->_transform))
		{
			$transform = $this->_transform;
		}

		return Craft::$app->getAssets()->getUrlForFile($this, $transform);
	}

	/**
	 * @inheritdoc
	 */
	public function getThumbUrl($size = 125)
	{
		if ($this->hasThumb())
		{
			return UrlHelper::getResourceUrl('assetthumbs/'.$this->id.'/'.$size, [
				Craft::$app->getResources()->dateParam => $this->dateModified->getTimestamp()
			]);
		}
		else
		{
			return false;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function getIconUrl($size = 125)
	{
		if ($this->hasThumb())
		{
			return false;
		}
		else
		{
			return UrlHelper::getResourceUrl('icons/'.$this->getExtension().'/'.$size);
		}
	}

	/**
	 * Does this file have a thumbnail?
	 *
	 * @return bool
	 */
	public function hasThumb()
	{
		if ($this->kind == 'image' && $this->height && $this->width)
		{
			// Gd doesn't process bitmaps
			if ($this->getExtension() == 'bmp' && Craft::$app->getImages()->isGd())
			{
				return false;
			}

			return true;
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
		return IOHelper::getExtension($this->filename);
	}

	/**
	 * @return string
	 */
	public function getMimeType()
	{
		return IOHelper::getMimeType($this->filename);
	}

	/**
	 * Get image height.
	 *
	 * @param string|null $transform The optional transform handle for which to get thumbnail.
	 *
	 * @return bool|float|mixed
	 */

	public function getHeight($transform = null)
	{
		if ($transform !== null && !ImageHelper::isImageManipulatable($this->getExtension()))
		{
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
	public function getWidth($transform = null)
	{
		if ($transform !== null && !ImageHelper::isImageManipulatable($this->getExtension()))
		{
			$transform = null;
		}

		return $this->_getDimension('width', $transform);
	}

	/**
	 * @return string
	 */
	public function getTransformSource()
	{
		if (!$this->_transformSource)
		{
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
	 * @return string
	 */
	public function getUri($filename = null)
	{
		$folder = $this->getFolder();
		return $folder->path.($filename ?: $this->filename);
	}

	/**
	 * Return the path where the source for this Asset's transforms should be.
	 *
	 * @return string
	 */
	public function getImageTransformSourcePath()
	{
		$volume = Craft::$app->getVolumes()->getVolumeById($this->volumeId);

		if ($volume->isLocal())
		{
			return $volume->getRootPath().'/'.$this->getUri();
		}
		else
		{
			return Craft::$app->getPath()->getAssetsImageSourcePath().'/'.$this->id.'.'.$this->getExtension();
		}
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
		if ($this->kind != 'image')
		{
			return null;
		}

		if ($transform === null && isset($this->_transform))
		{
			$transform = $this->_transform;
		}

		if (!$transform)
		{
			return $this->$dimension;
		}

		$transform = Craft::$app->getAssetTransforms()->normalizeTransform($transform);

		$dimensions = [
			'width' => $transform->width,
			'height' => $transform->height
		];

		if (!$transform->width || !$transform->height)
		{
			// Fill in the blank
			list($dimensions['width'], $dimensions['height']) = ImageHelper::calculateMissingDimension($dimensions['width'], $dimensions['height'], $this->width, $this->height);
		}

		// Special case for 'fit' since that's the only one whose dimensions vary from the transform dimensions
		if ($transform->mode == 'fit')
		{
			$factor = max($this->width / $dimensions['width'], $this->height / $dimensions['height']);
			$dimensions['width']  = round($this->width / $factor);
			$dimensions['height'] = round($this->height / $factor);
		}

		return $dimensions[$dimension];
	}
}
