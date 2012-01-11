<?php

class Routes extends BaseModel
{
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

	protected $belongsTo = array(
		'site' => array('model' => 'Sites', 'required' => true)
	);

	protected $attributes = array(
		'route'      => array('type' => AttributeType::String, 'maxSize' => 500, 'required' => true),
		'template'   => array('type' => AttributeType::String, 'maxSize' => 250, 'required' => true),
		'sort_order' => array('type' => AttributeType::Integer, 'required' => true)
	);
}
