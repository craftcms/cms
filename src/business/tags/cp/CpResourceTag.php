<?php

class CpResourceTag extends Tag
{
	private $_path;
	private $_url;

	protected function init($path = '')
	{
		$this->_path = $path;
	}

	private function getUrl()
	{
		if (!isset($this->_url))
		{
			$this->_url = UrlHelper::generateResourceUrl($this->_path);
		}

		return $this->_url;
	}

	public function __toString()
	{
		return $this->getUrl();
	}

	public function url()
	{
		return $this->getUrl();
	}

	public function js()
	{
		return '<script type="text/javascript" src="'.$this->getUrl().'"></script>';
	}

	public function css()
	{
		return '<link rel="stylesheet" type="text/css" href="'.$this->getUrl().'" />';
	}
}
