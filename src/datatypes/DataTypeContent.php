<?php

class DataTypeContent extends BlocksDataType
{
	private $_dataType;

	private static $attributes = array(
		'num'    => array('type' => AttributeType::Integer, 'required' => true),
		'label'  => array('type' => AttributeType::String, 'maxSize' => 150),
		'active' => array('type' => AttributeType::Boolean, 'required' => true),
		'type'   => array('type' => AttributeType::Enum, 'values' => 'published,draft,autosave', 'default' => 'draft', 'required' => true),
	);

	public function init($dataType)
	{
		$this->_dataType = $dataType;
	}

	public function getBelongsTo()
	{
		return array(
			strtolower($this->_dataType) => $this->_dataType,
			'content' => 'Content'
		);
	}
}
