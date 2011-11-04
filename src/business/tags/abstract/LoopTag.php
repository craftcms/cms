<?php

abstract class LoopTag extends Tag
{
	public function toArray()
	{
		$vals = array();

		foreach ($this->_val as $val)
			$vals[] = $val;

		return $vals;
	}

	public function total()
	{
		return new NumericTag(count($this->toArray()));
	}
}
