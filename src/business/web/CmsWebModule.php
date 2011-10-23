<?php

class CmsWebModule extends CWebModule
{
	private $_viewPath;
	private $_layoutPath;

	/*public function getViewPath()
	{
		if($this->_viewPath !== null)
			return $this->_viewPath;
		else
		{
			$relativePath = substr($this->getBasePath(), strlen(realpath(BLOCKS_APP_BLOCKS_PATH).'modules/') + 1);
			$fullPath = str_replace('\\', '/', Blocks::app()->getViewPath().$relativePath.'/');
			$this->_viewPath = $fullPath;
			return $this->_viewPath;
		}
	}*/

	/**
	 * @return string the root directory of view files. Defaults to 'protected/views'.
	 */
	public function getViewPath()
	{
		if($this->_viewPath!==null)
			return $this->_viewPath;
		else
			return $this->_viewPath=$this->getBasePath().DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR;
	}

	public function getLayoutPath()
	{
		if ($this->_layoutPath !== null)
			return $this->_layoutPath;
		else
			return $this->_layoutPath = $this->getViewPath().'layouts'.DIRECTORY_SEPARATOR;
	}
}
