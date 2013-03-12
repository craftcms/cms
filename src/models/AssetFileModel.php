<?php
namespace Craft;

/**
 *
 */
class AssetFileModel extends BaseElementModel
{
	protected $elementType = ElementType::Asset;

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
	 * @return AssetFolderModel|null
	 */
	public function getFolder()
	{
		return craft()->assets->getFolderById($this->folderId);
	}

	/**
	 * Returns the URL to the file.
	 *
	 * @param string|null $transform
	 * @return mixed
	 */
	public function getUrl($transform = null)
	{
		return craft()->assets->getUrlForFile($this, $transform);
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
			$height = 125;
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

		// treat the image as a horizontal?
		if (($this->height / $this->width) <= ($maxHeight / $maxWidth))
		{
			$thumbWidth = $maxWidth;
			$thumbHeight = round(($maxWidth / $this->width) * $this->height);
		}
		else
		{
			$thumbHeight = $maxHeight;
			$thumbWidth = round(($maxHeight / $this->height) * $this->width);
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
	 * Return a dimension of the image.
	 *
	 * @param $dimension 'height' or 'width'
	 * @param $transform
	 * @return bool|float|mixed
	 */
	private function _getDimension($dimension, $transform)
	{
		if ($this->kind != 'image')
		{
			return false;
		}

		if (!$transform)
		{
			return $this->getAttribute($dimension);
		}

		$transform = craft()->assetTransforms->normalizeTransform($transform);

		$dimensions = array(
			'width' => $transform->width,
			'height' => $transform->height
		);

		if (!$transform->width || !$transform->height)
		{
			// Fill in the blank
			list($dimensions['width'], $dimensions['height']) = Image::calculateMissingDimension($dimensions['width'], $dimensions['height'], $this->width, $this->height);
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
