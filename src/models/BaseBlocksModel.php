<?php

abstract class BaseBlocksModel extends BaseModel
{
	protected $foreignKey;
	protected $model;

	/**
	 * Returns an instance of the specified model
	 *
	 * @param string $class
	 *
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

	public function init()
	{
		$this->belongsTo = array(
			$this->foreignKey => $this->model,
			'block'           => 'ContentBlocks'
		);
	}
}
