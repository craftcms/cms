<?php
namespace Blocks;

/**
 *
 */
class PluginRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'plugins';
	}

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'class'       => array(AttributeType::ClassName, 'required' => true),
			'version'     => array(AttributeType::Version, 'required' => true),
			'enabled'     => AttributeType::Bool,
			'settings'    => AttributeType::Mixed,
			'installDate' => array(AttributeType::DateTime, 'required' => true),
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'migrations' => array(static::HAS_MANY, 'MigrationRecord', 'pluginId'),
		);
	}
}
