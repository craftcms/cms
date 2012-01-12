<?php

/**
 *
 */
class FileTag extends Tag
{
	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function __toString()
	{
		return $this->path();
	}

	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function path()
	{
		return $this->_val->path;
	}

	/**
	 * @access public
	 *
	 * @return UploadFolderTag
	 */
	public function folder()
	{
		$folder = Blocks::app()->assets->getUploadFolderById($this->_val->upload_folder_id);
		return new UploadFolderTag($folder);
	}
}
