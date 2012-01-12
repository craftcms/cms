<?php

/**
 *
 */
class UserGroupBlocks extends BaseBlocksModel
{
	protected $model = 'UserGroups';
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
