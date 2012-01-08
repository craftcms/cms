<?php

class Routes extends BlocksModel
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

	protected static $belongsTo = array(
		'site' => 'Sites'
	);

	protected static $attributes = array(
		'route'      => array('type' => AttributeType::String, 'maxSize' => 500, 'required' => true),
		'template'   => array('type' => AttributeType::String, 'maxSize' => 250, 'required' => true),
		'sort_order' => array('type' => AttributeType::Integer, 'required' => true)
	);
}
