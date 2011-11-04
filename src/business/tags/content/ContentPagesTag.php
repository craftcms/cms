<?php

class ContentPagesTag extends LoopTag
{
	public function __toTagArray($params = array())
	{
		$tags = array();

		foreach ($this->_val as $page)
		{
			$tags[] = new ContentPagesTag($page);
		}

		return $tags;
	}
}
