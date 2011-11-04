<?php

class ContentSectionsTag extends LoopTag
{
	public function __toTagArray($params = array())
	{
		$tags = array();

		foreach ($this->_val as $section)
		{
			$tags[] = new ContentSectionsTag($section);
		}

		return $tags;
	}
}
