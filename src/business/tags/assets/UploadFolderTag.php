<?php

class UploadFolderTag extends Tag
{
	public function __toString()
	{
		return $this->relativePath();
	}

	public function name()
	{
		return $this->_val->name;
	}

	public function relativePath()
	{
		return $this->_val->relative_path;
	}

	public function includeSubFolders()
	{
		return (bool) $this->_val->include_subfolders;
	}

	public function site()
	{
		return new SiteTag($this->_val->site_id);
	}

	public function files()
	{
		$files = Blocks::app()->assets->getFilesForUploadFolder($this->_val->id);
		return new FilesTag($files);
	}
}
