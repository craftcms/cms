<?php

/**
 *
 */
class ContentBlocks extends BaseModel
{
	/**
	 * @return array
	 */
	protected $attributes = array(
		'handle'       => array('type' => bAttributeType::String, 'maxLength' => 150, 'required' => true),
		'label'        => array('type' => bAttributeType::String, 'maxLength' => 500, 'required' => true),
		'class'        => array('type' => bAttributeType::String, 'maxLength' => 150, 'required' => true),
		'instructions' => array('type' => bAttributeType::Text)
	);

	/**
	 * Returns an instance of the specified model
	 * @static
	 * @param string $class
	 * @return object The model instance
	*/
	public static function model($class = __CLASS__)
	{
		return parent::model($class);
	}
}
