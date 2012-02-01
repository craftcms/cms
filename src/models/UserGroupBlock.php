<?php
namespace Blocks;

/**
 *
 */
class UserGroupBlock extends BaseBlocksModel
{
	protected $tableName = 'usergroupblocks';
	protected $model = 'UserGroup';
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
