<?php

/**
 *
 */
class Content extends BaseModel
{
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

	protected $attributes = array(
		'language_code' => array('type' => AttributeType::String, 'maxSize' => 5, 'required' => true)
	);
}
