<?php

/**
 *
 */
class bUserContent extends bBaseContentModel
{
	protected $tableName = 'usercontent';
	protected $model = 'bUser';
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
