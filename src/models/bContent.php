<?php

/**
 *
 */
class Content extends bBaseModel
{
	protected $tableName = 'content';

	protected $attributes = array(
		'language_code' => array('type' => bAttributeType::String, 'maxLength' => 5, 'required' => true)
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
