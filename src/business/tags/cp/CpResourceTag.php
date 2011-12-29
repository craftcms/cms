<?php

class CpResourceTag extends Tag
{
	private $path;
	private $url;

	protected function init($path = '')
	{
		$this->path = $path;
	}

	private function getUrl()
	{
		if (!isset($this->url))
		{
			$this->url = UrlHelper::generateResourceUrl($this->path);
		}

		return $this->url;
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
