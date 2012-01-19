<?php

/**
 *
 */
class bUploadFolderTag extends bTag
{
	/**
	 * @return mixed
	 */
	public function __toString()
	{
		return $this->relativePath();
	}

	/**
	 * @return mixed
	 */
	public function name()
	{
		return $this->_val->name;
	}

	/**
	 * @return mixed
	 */
	public function relativePath()
	{
		return $this->_val->relative_path;
	}

	/**
	 * @return bool
	 */
	public function includeSubFolders()
	{
		return (bool) $this->_val->include_subfolders;
	}

	/**
	 * @return SiteTag
	 */
	public function site()
	{
		return new SiteTag($this->_val->site_id);
	}

	/**
	 * @return bFilesTag
	 */
	public function files()
	{
		$files = Blocks::app()->assets->getFilesForUploadFolder($this->_val->id);
		return new bFilesTag($files);
	}
}
