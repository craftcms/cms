<?php
namespace Blocks;

/**
 *
 */
class UserContent extends BaseContentModel
{
	protected $tableName = 'usercontent';
	protected $model = 'User';
	protected $foreignKey = 'user';
}
