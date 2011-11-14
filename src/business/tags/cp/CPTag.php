<?php

class CPTag extends Tag
{
	public function sections()
	{
		return new CPSectionsTag();
	}

	public function baseUrl()
	{
		$baseUrl = Blocks::app()->request->getScriptFile().'/';
		return new StringTag($baseUrl);
	}
}
