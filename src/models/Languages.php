<?php

class Languages
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

	protected $attributes = array(
		'language_code' => array('type' => AttributeType::String, 'maxSize' => 5)
	);
}
