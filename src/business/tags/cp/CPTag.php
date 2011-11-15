<?php

class CPTag extends Tag
{
	public function sections()
	{
		return new CPSectionsTag();
	}

	public function baseUrl()
	{
		return new StringTag('/admin.php'.'/');
	}
}
