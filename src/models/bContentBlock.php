<?php

/**
 *
 */
class bContentBlock extends bBaseModel
{
	protected $tableName = 'contentblocks';

	/**
	 * @return array
	 */
	protected $attributes = array(
		'handle'       => array('type' => bAttributeType::String, 'maxLength' => 150, 'required' => true),
		'label'        => array('type' => bAttributeType::String, 'maxLength' => 500, 'required' => true),
		'class'        => array('type' => bAttributeType::String, 'maxLength' => 150, 'required' => true),
		'instructions' => array('type' => bAttributeType::Text)
	);

	protected $belongsTo = array(
		'site' => array('model' => 'bSite', 'required' => true)
	);

	protected $indexes = array(
		array('column' => 'site_id,handle', 'unique' => true)
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
