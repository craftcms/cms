<?php

/**
 * @abstract
 */
abstract class bBaseContentModel extends bBaseModel
{
	protected $foreignKey;
	protected $model;

	protected $attributes = array(
		'num'    => array('type' => bAttributeType::Int, 'required' => true, 'unsigned' => true),
		'name'   => bAttributeType::Name,
		'active' => bAttributeType::Boolean,
		'type'   => array('type' => bAttributeType::Enum, 'values' => array('published','draft','autosave'), 'default' => 'draft', 'required' => true),
	);

	/**
	 * Dynamically set $this->belongsTo from $this->foreignKey and $this->model
	 */
	public function init()
	{
		$this->belongsTo = array(
			$this->foreignKey => array('model' => $this->model, 'required' => true),
			'content'         => array('model' => 'bContent', 'required' => true)
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
