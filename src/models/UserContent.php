<?php

class UserContent extends BaseContentModel
{
	protected $model = 'Users';
	protected $foreignKey = 'user';

	/**
	 * Returns an instance of the specified model
	 *
	 * @param string $class
	 *
	 * @return object The model instance
	 * @static
	*/
	public static function model($class = __CLASS__)
	{
		return parent::model($class);
	}
}
