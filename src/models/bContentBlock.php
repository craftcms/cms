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
		'name'         => bAttributeType::Name,
		'handle'       => bAttributeType::Handle,
		'class'        => bAttributeType::ClassName,
		'instructions' => bAttributeType::Text
	);

	protected $belongsTo = array(
		'site' => array('model' => 'bSite', 'required' => true)
	);

	protected $indexes = array(
		array('columns' => array('site_id','handle'), 'unique' => true)
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
