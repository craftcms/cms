<?php

class UserGroupMembers extends BlocksModel
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

	protected $belongsTo = array(
		'user'  => array('model' => 'Users', 'required' => true),
		'group' => array('model' => 'Groups', 'required' => true)
	);
}
