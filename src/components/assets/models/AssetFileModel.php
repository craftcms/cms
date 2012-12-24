<?php
namespace Blocks;

/**
 *
 */
class AssetFileModel extends BaseEntityModel
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
			'dateModified'	=> AttributeType::DateTime
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
	 * @param string|null $sizeHandle
	 * @return string|null
	 */
	public function getUrl($sizeHandle = null)
	{
		return blx()->assetSources->getSourceTypeById($this->sourceId)->getSettings()->url .
			$this->getFolder()->fullPath .
			($sizeHandle ? '_'.$sizeHandle.'/' : '') .
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
	 * Gets the blocks.
	 *
	 * @access protected
	 * @return array
	 */
	protected function getBlocks()
	{
		return blx()->assets->getAllBlocks();
	}

	/**
	 * Gets the content.
	 *
	 * @access protected
	 * @return AssetContentRecord
	 */
	protected function getContent()
	{
		return blx()->assets->getFileContentRecordByFileId($this->id);
	}
}
