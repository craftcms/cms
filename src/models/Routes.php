<?php

/**
 *
 */
class Routes extends BaseModel
{
	protected $attributes = array(
		'route'      => array('type' => AttributeType::String, 'maxLength' => 500, 'required' => true),
		'template'   => array('type' => AttributeType::String, 'required' => true),
		'sort_order' => array('type' => AttributeType::Integer, 'required' => true, 'unsigned' => true)
	);

	protected $belongsTo = array(
		'site' => array('model' => 'Sites', 'required' => true)
	);

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
