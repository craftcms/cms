<?php

/**
 *
 */
class FileTag extends Tag
{
	/**
	 * @return mixed
	 */
	public function __toString()
	{
		return $this->path();
	}

	/**
	 * @return mixed
	 */
	public function path()
	{
		return $this->_val->path;
	}

	/**
	 * @return UploadFolderTag
	 */
	public function folder()
	{
		$folder = Blocks::app()->assets->getUploadFolderById($this->_val->upload_folder_id);
		return new UploadFolderTag($folder);
	}
}
