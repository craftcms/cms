<?php

/**
 *
 */
class bEntryContent extends bBaseContentModel
{
	protected $tableName = 'entrycontent';
	protected $model = 'bEntry';
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
