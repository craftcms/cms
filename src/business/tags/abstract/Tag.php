<?php

class Tag
{
	public function __toString()
	{
		return '';
	}

	public function __toBool()
	{
		return (bool)$this->__toString();
	}

	public function __toArray()
	{
		$tags = array();

		$letters = str_split($this->__toString());

		foreach ($letters as $letter)
		{
			$tags[] = new StringTag($letter);
		}

		return $tags;
	}

	public function __call($name, $args)
	{
		return new Tag();
	}
}
