<?php

/**
 *
 */
class SiteSettings extends BaseSettingsModel
{
	protected $model = 'Sites';
	protected $foreignKey = 'site';

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
