<?php

class AssetsTag extends Tag
{
	private $_siteId;

	function __construct()
	{
		$this->_siteId = Blocks::app()->request->getSiteInfo()->id;
	}

	public function folders()
	{
		$folders = Blocks::app()->assets->getUploadFoldersBySiteId($this->_siteId);
		return new UploadFoldersTag($folders);
	}

	public function files()
	{
		$files = Blocks::app()->assets->getAllFilesBySiteId($this->_siteId);
		return new FilesTag($files);
	}

	public function file($id)
	{
		$file = Blocks::app()->assets->getFileById($id);
		return new FileTag($file);
	}
}
