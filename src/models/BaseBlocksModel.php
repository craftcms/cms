<?php

/**
 * @abstract
 */
abstract class BaseBlocksModel extends BaseModel
{
	protected $foreignKey;
	protected $model;

	protected $attributes = array(
		'required'     => array('type' => AttributeType::Boolean, 'required' => true, 'unsigned' => true),
		'sort_order'   => array('type' => AttributeType::Integer, 'required' => true, 'unsigned' => true)
	);

	/**
	 * Dynamically set $this->belongsTo from $this->foreignKey and $this->model
	 */
	public function init()
	{
		$this->belongsTo = array(
			$this->foreignKey => $this->model,
			'block'           => 'ContentBlocks'
		);
	}

	/**
	 * Returns an instance of the specified model
	 * @return object The model instance
	 * @static
	 */
	public static function model($class = __CLASS__)
	{
		return parent::model($class);
	}
}
