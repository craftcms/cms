<?php
namespace Blocks;

/**
 *
 */
class ContentBlockSetting extends BaseSettingsModel
{
	protected $tableName = 'contentblocksettings';
	protected $model = 'ContentBlock';
	protected $foreignKey = 'block';
}
