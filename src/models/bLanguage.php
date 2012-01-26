<?php

/**
 *
 */
class bLanguage extends bBaseModel
{
	protected $tableName = 'languages';

	protected $attributes = array(
		'language_code' => array('type' => bAttributeType::String, 'maxLength' => 5)
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
