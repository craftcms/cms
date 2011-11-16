<?php

class ResourceTag extends Tag
{
	public function url($resourcePath)
	{
		$url = BlocksHtml::getResourceUrl($resourcePath);
		return new StringTag($url);
	}

	public function js($resourcePath)
	{
		$url = BlocksHtml::getResourceUrl($resourcePath);
		$tag = '<script type="text/javascript" src="'.$url.'"></script>';
		return new StringTag($tag);
	}

	public function css($resourcePath)
	{
		$url = BlocksHtml::getResourceUrl($resourcePath);
		$tag = '<link rel="stylesheet" type="text/css" href="'.$url.'" />';
		return new StringTag($tag);
	}
}
