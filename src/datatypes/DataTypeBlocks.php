<?php

class DataTypeBlocks extends BlocksDataType
{
	private $_dataType;

	private static $attributes = array(
		'required'     => array('type' => AttributeType::Boolean, 'required' => true),
		'sort_order'   => array('type' => AttributeType::Integer, 'required' => true)
	);

	public function init($dataType)
	{
		$this->_dataType = $dataType;
	}

	public function getBelongsTo()
	{
		return array(
			strtolower($this->_dataType) => $this->_dataType,
			'block' => 'ContentBlocks'
		);
	}
}
