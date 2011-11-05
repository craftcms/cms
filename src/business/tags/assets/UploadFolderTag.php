<?php

class UploadFolderTag extends Tag
{
	public function __toString()
	{
		return $this->relativePath();
	}

	public function name()
	{
		return new StringTag($this->_val->name);
	}

	public function relativePath()
	{
		return new StringTag($this->_val->relative_path);
	}

	public function includeSubFolders()
	{
		return new BoolTag($this->_val->include_subfolders);
	}

	public function site()
	{
		return new SiteTag($this->_val->site_id);
	}

	public function files()
	{
		$files = Blocks::app()->assetRepo->getFilesForUploadFolder($this->_val->id);
		return new FilesTag($files);
	}
}
