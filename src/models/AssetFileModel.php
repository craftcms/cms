<?php
namespace Blocks;

/**
 *
 */
class AssetFileModel extends ElementModel
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
	 * @return array
	 */
	public function defineAttributes()
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
		return blx()->assets->getFolderById($this->folderId);
	}

	/**
	 * Returns the URL to the file.
	 *
	 * @param string|null $sizeHandle
	 * @return string|null
	 */
	public function getUrl($sizeHandle = null)
	{
		return blx()->assetSources->getSourceTypeById($this->sourceId)->getSettings()->url.
			$this->getFolder()->fullPath.
			($sizeHandle ? '_'.$sizeHandle.'/' : '').
			$this->filename;
	}

	/**
	 * Get the thumb's URL.
	 *
	 * @param int $size
	 * @return string
	 */
	public function getThumbUrl($size = 125)
	{
		if (!is_numeric($size))
		{
			$size = 125;
		}

		return UrlHelper::getResourceUrl('assetthumbs/'.$this->id.'/'.$size);
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

}
