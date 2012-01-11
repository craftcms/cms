<?php

class EntryContent extends BaseContentModel
{
	protected $model = 'Entries';
	protected $foreignKey = 'entry';

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
