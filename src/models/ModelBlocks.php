<?php

class ModelBlocks extends BlocksModel
{
	/**
	 * Returns an instance of the specified model
	 * @return object The model instance
	 * @static
	 */
	public static function model($class = __CLASS__)
	{
		return parent::model($class);
	}

	protected $attributes = array(
		'required'     => array('type' => AttributeType::Boolean, 'required' => true),
		'sort_order'   => array('type' => AttributeType::Integer, 'required' => true)
	);

	public function getBelongsTo()
	{
		return array(
			strtolower($this->_blocksModel) => $this->_blocksModel,
			'block' => 'ContentBlocks'
		);
	}
}
