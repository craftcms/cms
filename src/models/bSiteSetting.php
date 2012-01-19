<?php

/**
 *
 */
class bSiteSettings extends bBaseSettingsModel
{
	protected $tableName = 'sitesettings';
	protected $model = 'bSite';
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
