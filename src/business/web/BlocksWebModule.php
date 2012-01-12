<?php

/**
 *
 */
class BlocksWebModule extends CWebModule
{
	private $_viewPath;
	private $_layoutPath;

	/**
	 * @access public
	 *
	 * @return string
	 */
	public function getViewPath()
	{
		if($this->_viewPath!==null)
			return $this->_viewPath;
		else
			return $this->_viewPath=$this->basePath.'/templates/';
	}

	/**
	 * @access public
	 *
	 * @return string
	 */
	public function getLayoutPath()
	{
		if ($this->_layoutPath !== null)
			return $this->_layoutPath;
		else
			return $this->_layoutPath = $this->viewPath.'layouts/';
	}
}
