<?php

/**
 *
 */
class bUserGroupBlock extends bBaseBlocksModel
{
	protected $tableName = 'usergroupblocks';
	protected $model = 'bUserGroup';
	protected $foreignKey = 'group';

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
