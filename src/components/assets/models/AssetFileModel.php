<?php
namespace Blocks;

/**
 *
 */
class AssetFileModel extends BaseModel
{

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
		return array(
			'id'			=> AttributeType::Number,
			'sourceId'		=> AttributeType::Number,
			'folderId'		=> AttributeType::Number,
			'contentId'		=> AttributeType::Number,
			'filename'		=> AttributeType::String,
			'originalName'	=> AttributeType::String,
			'kind'			=> AttributeType::String,
			'width'			=> AttributeType::Number,
			'height'		=> AttributeType::Number,
			'size'			=> AttributeType::Number,
			'dateModified'	=> AttributeType::Number
		);
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
	 * @return string|null
	 */
	public function getUrl()
	{
		return blx()->assetSources->getSourceTypeById($this->sourceId)->getSettings()->url . $this->getFolder()->fullPath . $this->filename;
	}

	/**
	 * Returns the size of the thumbnail.
	 *
	 * @param int $maxSize
	 * @return array
	 */
	public function getThumbSize($maxSize = 125)
	{
		$path = blx()->assetSources->getSourceTypeById($this->sourceId)->getSettings()->path . $this->getFolder()->fullPath . $this->filename;
		list($width, $height) = getimagesize($path);

		if ($width > $height)
		{
			$thumbWidth = $maxSize;
			$thumbHeight = $height * ($maxSize / max(1, $width));
		}
		else
		{
			$thumbHeight = $maxSize;
			$thumbWidth = $width * ($maxSize / max(1, $height));
		}

		return array('width' => $thumbWidth, 'height' => $thumbHeight);
	}
}
