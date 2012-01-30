<?php
namespace Blocks;

/**
 *
 */
class EntryContent extends BaseContentModel
{
	protected $tableName = 'entrycontent';
	protected $model = 'Entry';
	protected $foreignKey = 'entry';

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
}
