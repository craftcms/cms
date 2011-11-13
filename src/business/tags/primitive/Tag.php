<?php

class Tag
{
	public function __toString()
	{
		return '';
	}

	public function __toBool()
	{
		return false;
	}

	public function __toArray()
	{
		return array();
	}

	public function __call($name, $args)
	{
		return new Tag;
	}

	public function classname()
	{
		return new StringTag(get_class($this));
	}
}
