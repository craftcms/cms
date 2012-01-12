<?php

/**
 *
 */
class AssetsTag extends Tag
{
	private $_siteId;

	/**
	 * @access public
	 */
	function __construct()
	{
		$this->_siteId = Blocks::app()->site->currentSiteByUrl->id;
	}

	/**
	 * @access public
	 *
	 * @return UploadFoldersTag
	 */
	public function folders()
	{
		$folders = Blocks::app()->assets->getUploadFoldersBySiteId($this->_siteId);
		return new UploadFoldersTag($folders);
	}

	/**
	 * @access public
	 *
	 * @return FilesTag
	 */
	public function files()
	{
		$files = Blocks::app()->assets->getAllFilesBySiteId($this->_siteId);
		return new FilesTag($files);
	}

	/**
	 * @access public
	 *
	 * @param $id
	 *
	 * @return FileTag
	 */
	public function file($id)
	{
		$file = Blocks::app()->assets->getFileById($id);
		return new FileTag($file);
	}
}
