<?php
namespace Blocks;

/**
 *
 */
class UserGroupMember extends BaseModel
{
	protected $tableName = 'usergroupmembers';

	protected $belongsTo = array(
		'user'  => array('model' => 'bUser', 'required' => true),
		'group' => array('model' => 'bUserGroup', 'required' => true)
	);

	protected $indexes = array(
		array('columns' => array('user_id', 'group_id'), 'unique' => true)
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
