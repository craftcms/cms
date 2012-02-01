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
}
