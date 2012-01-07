<?php

class ModelContent extends BlocksModel
{
	private $_blocksModel;

	private static $attributes = array(
		'num'    => array('type' => AttributeType::Integer, 'required' => true),
		'label'  => array('type' => AttributeType::String, 'maxSize' => 150),
		'active' => array('type' => AttributeType::Boolean, 'required' => true),
		'type'   => array('type' => AttributeType::Enum, 'values' => 'published,draft,autosave', 'default' => 'draft', 'required' => true),
	);

	public function init($blocksModel)
	{
		$this->_blocksModel = $blocksModel;
	}

	public function getBelongsTo()
	{
		return array(
			strtolower($this->_blocksModel) => $this->_blocksModel,
			'content' => 'Content'
		);
	}
}
