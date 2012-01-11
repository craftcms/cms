<?php

class SiteSettings extends BaseSettingsModel
{
	protected $model = 'Sites';
	protected $foreignKey = 'site';

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
