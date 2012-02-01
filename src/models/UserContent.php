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
