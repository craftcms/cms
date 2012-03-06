<?php
namespace Blocks;

/**
 *
 */
class BlockSetting extends BaseSettingsModel
{
	protected $tableName = 'contentblocksettings';
	protected $model = 'Block';
	protected $foreignKey = 'block';
}
