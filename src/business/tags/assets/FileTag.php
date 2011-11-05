<?php

class FileTag extends Tag
{
	public function __toString()
	{
		return $this->path();
	}

	public function path()
	{
		return new StringTag($this->_val->path);
	}

	public function folder()
	{
		$folder = Blocks::app()->assetRepo->getUploadFolderById($this->_val->upload_folder_id);
		return new UploadFolderTag($folder);
	}
}
