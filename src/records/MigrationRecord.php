<?php
namespace Craft;

/**
 * Class MigrationRecord
 *
 * @package craft.app.records
 */
class MigrationRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'migrations';
	}

	/**
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'version' => array(AttributeType::String, 'column' => ColumnType::Varchar, 'maxLength' => 255, 'required' => true),
			'applyTime' => array(AttributeType::DateTime, 'required' => true),
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'plugin' => array(static::BELONGS_TO, 'PluginRecord', 'onDelete' => static::CASCADE),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('version'), 'unique' => true),
		);
	}
}
