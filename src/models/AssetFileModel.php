<?php
namespace Craft;

/**
 * Class AssetFileModel
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.models
 * @since     1.0
 */
class AssetFileModel extends BaseElementModel
{
	// Properties
	// =========================================================================

	/**
	 * @var string
	 */
	protected $elementType = ElementType::Asset;

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
	 * Use the entry's title as its string representation.
	 *
	 * @return string
	 */
	public function __toString()
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

	/**
	 * Checks if an attribute value is set.
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public function __isset($name)
	{
		// Is it a transform handle?
		$transform = craft()->assetTransforms->getTransformByHandle($name);

		if ($transform)
		{
			return true;
		}
		else
		{
			return parent::__isset($name);
		}
	}

	/**
	 * Magic getter
	 *
	 * @param string $name
	 *
	 * @throws \Exception
	 * @return mixed
	 */
	public function __get($name)
	{
		// Run through the BaseModel/CModel stuff first
		try
		{
			return parent::__get($name);
		}
		catch (\Exception $e)
		{
			// Is $name a transform handle?
			$transform = craft()->assetTransforms->getTransformByHandle($name);
			if ($transform)
			{
				// Duplicate this model and set it to that transform
				$model = new AssetFileModel();

				// Can't just use getAttributes() here because we'll get thrown into an infinite loop.
				foreach ($this->attributeNames() as $attributeName)
				{
					$model->setAttribute($attributeName, parent::getAttribute($attributeName));
				}

				$model->setContent($this->getContent());
				$model->setTransform($transform);
				return $model;
			}

			// Fine, throw the exception
			throw $e;
		}
	}

	/**
	 * @inheritDoc BaseModel::getAttribute()
	 *
	 * @param string $name
	 * @param bool   $flattenValue
	 *
	 * @return mixed
	 */
	public function getAttribute($name, $flattenValue = false)
	{
		// Override 'width' and 'height' with getWidth() and getHeight() in case $this->_transform is set.
		if ($name == 'width')
		{
			return $this->getWidth();
		}
		else if ($name == 'height')
		{
			return $this->getHeight();
		}
		else
		{
			return parent::getAttribute($name, $flattenValue);
		}
	}

	/**
	 * @inheritDoc BaseElementModel::getFieldLayout()
	 *
	 * @return FieldLayoutModel|null
	 */
	public function getFieldLayout()
	{
		$source = $this->getSource();

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
				$field = craft()->fields->getFieldById($fieldId);
				$settings = $field->settings;

				if ($settings['useSingleFolder'])
				{
					$sourceId = $settings['singleUploadLocationSource'];
				}
				else
				{
					$sourceId = $settings['defaultUploadLocationSource'];
				}

				$source = craft()->assetSources->getSourceById($sourceId);

				if ($source)
				{
					return $source->getFieldLayout();
				}
			}
		}

		return null;
	}

	/**
	 * @inheritDoc BaseElementModel::isEditable()
	 *
	 * @return bool
	 */
	public function isEditable()
	{
		return craft()->userSession->checkPermission('uploadToAssetSource:'.$this->sourceId);
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
	 * @return AssetFolderModel|null
	 */
	public function getFolder()
	{
		return craft()->assets->getFolderById($this->folderId);
	}

	/**
	 * @return AssetSourceModel|null
	 */
	public function getSource()
	{
		return craft()->assetSources->getSourceById($this->sourceId);
	}

	/**
	 * Sets the transform.
	 *
	 * @param mixed $transform
	 *
	 * @return AssetFileModel
	 */
	public function setTransform($transform)
	{
		$this->_transform = craft()->assetTransforms->normalizeTransform($transform);
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

		return craft()->assets->getUrlForFile($this, $transform);
	}

	/**
	 * @inheritDoc BaseElementModel::getThumbUrl()
	 *
	 * @param int $size
	 *
	 * @return string
	 */
	public function getThumbUrl($size = 125)
	{
		if ($this->hasThumb())
		{
			return UrlHelper::getResourceUrl('assetthumbs/'.$this->id.'/'.$size, array(
				craft()->resources->dateParam => $this->dateModified->getTimestamp()
			));
		}
		else
		{
			return false;
		}
	}

	/**
	 * @inheritDoc BaseElementModel::getIconUrl()
	 *
	 * @param int $size
	 *
	 * @return string
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
		if ($this->kind == 'image' && $this->_getHeight() && $this->_getWidth())
		{
			// Gd doesn't process bitmaps
			if ($this->getExtension() == 'bmp' && craft()->images->isGd())
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
		if ($transform && !ImageHelper::isImageManipulatable($this->getExtension()))
		{
			return $this->_getDimension('height', null);
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
		if ($transform && !ImageHelper::isImageManipulatable($this->getExtension()))
		{
			return $this->_getDimension('width', null);
		}

		return $this->_getDimension('width', $transform);
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseModel::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array_merge(parent::defineAttributes(), array(
			'sourceId'		=> AttributeType::Number,
			'folderId'		=> AttributeType::Number,
			'filename'		=> AttributeType::String,
			'originalName'	=> AttributeType::String,
			'kind'			=> AttributeType::String,
			'width'			=> AttributeType::Number,
			'height'		=> AttributeType::Number,
			'size'			=> AttributeType::Number,
			'dateModified'  => AttributeType::DateTime
		));
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
	 * @return string
	 */
	public function getTransformSource()
	{
		if (!$this->_transformSource)
		{
			craft()->assetTransforms->getLocalImageSource($this);
		}

		return $this->_transformSource;
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns the actual width attribute, since $this->width gets routed to getWidth() now.
	 *
	 * @return mixed
	 */
	private function _getWidth()
	{
		return parent::getAttribute('width');
	}

	/**
	 * Returns the actual height attribute, since $this->height gets routed to getHeight() now.
	 *
	 * @return mixed
	 */
	private function _getHeight()
	{
		return parent::getAttribute('height');
	}

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
			return parent::getAttribute($dimension);
		}

		$transform = craft()->assetTransforms->normalizeTransform($transform);

		$dimensions = array(
			'width' => $transform->width,
			'height' => $transform->height
		);

		if (!$transform->width || !$transform->height)
		{
			// Fill in the blank
			list($dimensions['width'], $dimensions['height']) = ImageHelper::calculateMissingDimension($dimensions['width'], $dimensions['height'], $this->_getWidth(), $this->_getHeight());
		}

		// Special case for 'fit' since that's the only one whose dimensions vary from the transform dimensions
		if ($transform->mode == 'fit')
		{
			$factor = max($this->_getWidth() / $dimensions['width'], $this->_getHeight() / $dimensions['height']);
			$dimensions['width']  = round($this->_getWidth() / $factor);
			$dimensions['height'] = round($this->_getHeight() / $factor);
		}

		return $dimensions[$dimension];
	}
}
