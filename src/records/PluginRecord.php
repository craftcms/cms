<?php
namespace Craft;

/**
 * Class PluginRecord
 *
 * @package craft.app.records
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
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'class'       => array(AttributeType::ClassName, 'required' => true),
			'version'     => array('maxLength' => 15, 'column' => ColumnType::Char, 'required' => true),
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
