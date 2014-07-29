<?php
namespace Craft;

/**
 * Class MigrationRecord
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.records
 * @since     1.0
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
