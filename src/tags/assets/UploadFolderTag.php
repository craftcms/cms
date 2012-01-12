<?php

/**
 *
 */
class UploadFolderTag extends Tag
{
	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function __toString()
	{
		return $this->relativePath();
	}

	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function name()
	{
		return $this->_val->name;
	}

	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function relativePath()
	{
		return $this->_val->relative_path;
	}

	/**
	 * @access public
	 *
	 * @return bool
	 */
	public function includeSubFolders()
	{
		return (bool) $this->_val->include_subfolders;
	}

	/**
	 * @access public
	 *
	 * @return SiteTag
	 */
	public function site()
	{
		return new SiteTag($this->_val->site_id);
	}

	/**
	 * @access public
	 *
	 * @return FilesTag
	 */
	public function files()
	{
		$files = Blocks::app()->assets->getFilesForUploadFolder($this->_val->id);
		return new FilesTag($files);
	}
}
