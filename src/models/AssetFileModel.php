<?php
namespace Craft;

/**
 *
 */
class AssetFileModel extends BaseElementModel
{
	protected $elementType = ElementType::Asset;

	private $_transform;

	/**
	 * User the filename as the string representation.
	 *
	 * @return string
	 */
	function __toString()
	{
		return $this->filename;
	}

	/**
	 * Checks if an attribute value is set.
	 *
	 * @param string $name
	 * @return bool
	 */
	function __isset($name)
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
	 * @return mixed
	 */
	function __get($name)
	{
		// Is it a transform handle?
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

			$model->setTransform($transform);
			return $model;
		}
		else
		{
			return parent::__get($name);
		}
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array_merge(parent::defineAttributes(), array(
			'sourceId'		=> AttributeType::Number,
			'folderId'		=> AttributeType::Number,
			'contentId'		=> AttributeType::Number,
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
	 * Gets an attribute's value.
	 *
	 * @param string $name
	 * @param bool $flattenValue
	 * @return mixed
	 */
	public function getAttribute($name, $flattenValue = false)
	{
		// Override 'width' and 'height' with getWidth() and getHeight()
		// in case $this->_transform is set.
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
	 * @return AssetFolderModel|null
	 */
	public function getFolder()
	{
		return craft()->assets->getFolderById($this->folderId);
	}

	/**
	 * Sets the transform.
	 *
	 * @param mixed $transform
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
	 * Returns the URL to the element's icon image, if there is one.
	 *
	 * @param int|null $size
	 * @return string|false
	 */
	public function getIconUrl($size = null)
	{
		return $this->getThumbUrl($size, $size);
	}

	/**
	 * Get the thumb's URL.
	 *
	 * @param int $width
	 * @param int $height
	 * @return string
	 */
	public function getThumbUrl($width = 125, $height = 125)
	{
		if (!is_numeric($width))
		{
			$width = 125;
		}
		if (!is_numeric($height))
		{
			$height = $width;
		}

		return UrlHelper::getResourceUrl('assetthumbs/'.$this->id.'/'.$width.'x'.$height);
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
	 * Return thumbnail data.
	 *
	 * @param $maxWidth
	 * @param $maxHeight
	 * @return bool|object
	 */
	public function getThumbData($maxWidth, $maxHeight)
	{
		if ($this->kind != "image")
		{
			return false;
		}

		// In case getimagesize returned false.
		if (!$this->_getWidth() || !$this->_getHeight())
		{
			return false;
		}

		// treat the image as a horizontal?
		if (($this->_getHeight() / $this->_getWidth()) <= ($maxHeight / $maxWidth))
		{
			$thumbWidth = $maxWidth;
			$thumbHeight = round(($maxWidth / $this->_getWidth()) * $this->_getHeight());
		}
		else
		{
			$thumbHeight = $maxHeight;
			$thumbWidth = round(($maxHeight / $this->_getHeight()) * $this->_getWidth());
		}

		return (object) array(
			'url'     => $this->getThumbUrl($thumbWidth, $thumbHeight),
			'url2X'  => $this->getThumbUrl($thumbWidth*2, $thumbHeight*2),
			'width'   => $thumbWidth,
			'height'  => $thumbHeight,
		);
	}

	/**
	 * Get image height.
	 *
	 * @param string $transform optional transform handle for which to get thumbnail.
	 * @return bool|float|mixed
	 */

	public function getHeight($transform = null)
	{
		return $this->_getDimension('height', $transform);
	}

	/**
	 * Get image width.
	 *
	 * @param string $transform optional transform handle for which to get thumbnail.
	 * @return bool|float|mixed
	 */
	public function getWidth($transform = null)
	{
		return $this->_getDimension('width', $transform);
	}

	/**
	 * Returns the element's status.
	 *
	 * @return string|null
	 */
	public function getStatus()
	{
		return null;
	}

	/**
	 * Return a dimension of the image.
	 *
	 * @param $dimension 'height' or 'width'
	 * @param $transform
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
			list($dimensions['width'], $dimensions['height']) = Image::calculateMissingDimension($dimensions['width'], $dimensions['height'], $this->_getWidth(), $this->_getHeight());
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

	/**
	 * Returns the actual width attribute, since $this->width gets routed to getWidth() now.
	 *
	 * @access private
	 * @return mixed
	 */
	private function _getWidth()
	{
		return parent::getAttribute('width');
	}

	/**
	 * Returns the actual height attribute, since $this->height gets routed to getHeight() now.
	 *
	 * @access private
	 * @return mixed
	 */
	private function _getHeight()
	{
		return parent::getAttribute('height');
	}
}
