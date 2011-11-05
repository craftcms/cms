<?php

class AssetsTag extends Tag
{
	private $_siteId;

	// TODO: figure out what to do if service query returns null... exception?
	function __construct($siteId)
	{
		$this->_siteId = $siteId;
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
