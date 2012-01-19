<?php

/**
 *
 */
class bUserGroupMember extends bBaseModel
{
	protected $tableName = 'usergroupmembers';

	protected $belongsTo = array(
		'user'  => array('model' => 'bUser', 'required' => true),
		'group' => array('model' => 'Groups', 'required' => true)
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
