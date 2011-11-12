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

		$str = $this->__toString();

		if (strlen($str))
		{
			$letters = str_split($str);

			foreach ($letters as $letter)
			{
				$tags[] = new StringTag($letter);
			}
		}

		return $tags;
	}

	public function __call($name, $args)
	{
		return new Tag();
	}
}
