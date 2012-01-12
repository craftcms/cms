<?php

/**
 *
 */
class UserGroupPermissions extends BaseModel
{
	protected $attributes = array(
		'name' => array('type' => AttributeType::String, 'required' => true),
		'value' => array('type' => AttributeType::Integer, 'required' => true)
	);

	protected $belongsTo = array(
		'group' => array('model' => 'UserGroups', 'required' => true)
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
