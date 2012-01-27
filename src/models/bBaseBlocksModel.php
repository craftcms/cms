<?php

/**
 * @abstract
 */
abstract class bBaseBlocksModel extends bBaseModel
{
	protected $foreignKey;
	protected $model;

	protected $attributes = array(
		'required'     => array('type' => bAttributeType::Boolean, 'required' => true, 'unsigned' => true),
		'sort_order'   => array('type' => bAttributeType::Integer, 'required' => true, 'unsigned' => true)
	);

	/**
	 * Dynamically set $this->belongsTo from $this->foreignKey and $this->model
	 */
	public function init()
	{
		$this->belongsTo = array(
			$this->foreignKey => array('model' => $this->model, 'required' => true),
			'block'           => array('model' => 'bContentBlock', 'required' => true)
		);

		$this->indexes = array(
			array('columns' => array($this->foreignKey.'_id', 'block_id'), 'unique' => true)
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
