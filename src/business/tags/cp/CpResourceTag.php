<?php

class CpResourceTag extends Tag
{
	private $path;
	private $url;

	public function __construct($path = '')
	{
		$this->path = $path;
	}

	private function getUrl()
	{
		if (!isset($this->url))
		{
			$this->url = BlocksHtml::getResourceUrl($this->path);
		}

		return $this->url;
	}

	public function __toString()
	{
		return $this->getUrl();
	}

	public function url()
	{
		return new StringTag($this->getUrl());
	}

	public function js()
	{
		return new StringTag('<script type="text/javascript" src="'.$this->getUrl().'"></script>');
	}

	public function css()
	{
		return new StringTag('<link rel="stylesheet" type="text/css" href="'.$this->getUrl().'" />');
	}
}
