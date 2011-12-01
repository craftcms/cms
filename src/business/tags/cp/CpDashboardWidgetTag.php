<?php

class CpDashboardWidgetTag extends Tag
{
	private $_data;

	public function __construct($data)
	{
		$this->_data = $data;
	}

	public function className()
	{
		return new StringTag($this->_data['className']);
	}

	public function title()
	{
		return new StringTag($this->_data['title']);
	}

	public function body()
	{
		return new StringTag($this->_data['body']);
	}

	public function settings()
	{
		// settings could be either false or a string
		return self::_getVarTag($this->_data['settings']);
	}
}
