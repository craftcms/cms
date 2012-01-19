<?php

/**
 *
 */
class bAssetsTag extends bTag
{
	private $_siteId;

	/**
	 */
	function __construct()
	{
		$this->_siteId = Blocks::app()->site->currentSiteByUrl->id;
	}

	/**
	 * @return bUploadFoldersTag
	 */
	public function folders()
	{
		$folders = Blocks::app()->assets->getUploadFoldersBySiteId($this->_siteId);
		return new bUploadFoldersTag($folders);
	}

	/**
	 * @return bFilesTag
	 */
	public function files()
	{
		$files = Blocks::app()->assets->getAllFilesBySiteId($this->_siteId);
		return new bFilesTag($files);
	}

	/**
	 * @param $id
	 * @return bFileTag
	 */
	public function file($id)
	{
		$file = Blocks::app()->assets->getFileById($id);
		return new bFileTag($file);
	}
}
