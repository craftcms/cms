<?php

/**
 *
 */
class bContent extends bBaseModel
{
	protected $tableName = 'content';

	protected $attributes = array(
		'language_code' => bAttributeType::LanguageCode
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
