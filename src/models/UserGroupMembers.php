<?php

/**
 *
 */
class UserGroupMembers extends BaseModel
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

	protected $belongsTo = array(
		'user'  => array('model' => 'Users', 'required' => true),
		'group' => array('model' => 'Groups', 'required' => true)
	);
}
