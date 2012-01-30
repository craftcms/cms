<?php
namespace Blocks;

/**
 *
 */
class CpResourceTag extends Tag
{
	private $_path;
	private $_url;

	/**
	 * @access protected
	 * @param string $path
	 */
	protected function init($path = '')
	{
		$this->_path = $path;
	}

	/**
	 * @access private
	 * @return mixed
	 */
	private function getUrl()
	{
		if (!isset($this->_url))
		{
			$this->_url = UrlHelper::generateResourceUrl($this->_path);
		}

		return $this->_url;
	}

	/**
	 * @return mixed
	 */
	public function __toString()
	{
		return $this->getUrl();
	}

	/**
	 * @return mixed
	 */
	public function url()
	{
		return $this->getUrl();
	}

	/**
	 * @return string
	 */
	public function js()
	{
		return '<script type="text/javascript" src="'.$this->getUrl().'"></script>';
	}

	/**
	 * @return string
	 */
	public function css()
	{
		return '<link rel="stylesheet" type="text/css" href="'.$this->getUrl().'" />';
	}
}
