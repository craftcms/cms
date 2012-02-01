<?php
namespace Blocks;

/**
 *
 */
class SiteSetting extends BaseSettingsModel
{
	protected $tableName = 'sitesettings';
	protected $model = 'Site';
	protected $foreignKey = 'site';
}
