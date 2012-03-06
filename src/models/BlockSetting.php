<?php
namespace Blocks;

/**
 *
 */
class BlockSetting extends BaseSettingsModel
{
	protected $tableName = 'blocksettings';
	protected $model = 'Block';
	protected $foreignKey = 'block';
}
