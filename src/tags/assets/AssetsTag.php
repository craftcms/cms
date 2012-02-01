<?php
namespace Blocks;

/**
 *
 */
class AssetsTag extends Tag
{
	private $_siteId;

	/**
	 */
	function __construct()
	{
		$this->_siteId = Blocks::app()->site->currentSiteByUrl->id;
	}

	/**
	 * @return UploadFoldersTag
	 */
	public function folders()
	{
		$folders = Blocks::app()->assets->getUploadFoldersBySiteId($this->_siteId);
		return new UploadFoldersTag($folders);
	}

	/**
	 * @return FilesTag
	 */
	public function files()
	{
		$files = Blocks::app()->assets->getAllFilesBySiteId($this->_siteId);
		return new FilesTag($files);
	}

	/**
	 * @param $id
	 * @return FileTag
	 */
	public function file($id)
	{
		$file = Blocks::app()->assets->getFileById($id);
		return new FileTag($file);
	}
}
