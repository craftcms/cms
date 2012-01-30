<?php

/**
 *
 */
class bInfo extends bBaseModel
{
	protected $tableName = 'info';

	protected $attributes = array(
		'edition' => array('type' => bAttributeType::Enum, 'values' => array('Personal','Standard','Pro'), 'required' => true),
		'version' => bAttributeType::Version,
		'build'   => array('type' => bAttributeType::Int, 'required' => true, 'unsigned' => true)
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
