<?php

class ModelBlocks extends BlocksModel
{
	private $_blocksModel;

	private static $attributes = array(
		'required'     => array('type' => AttributeType::Boolean, 'required' => true),
		'sort_order'   => array('type' => AttributeType::Integer, 'required' => true)
	);

	public function init($blocksModel)
	{
		$this->_blocksModel = $blocksModel;
	}

	public function getBelongsTo()
	{
		return array(
			strtolower($this->_blocksModel) => $this->_blocksModel,
			'block' => 'ContentBlocks'
		);
	}
}
