<?php
namespace Craft;

/**
 * Class PluginRecord
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.records
 * @since     1.0
 */
class PluginRecord extends BaseRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseRecord::getTableName()
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'plugins';
	}

	/**
	 * @inheritDoc BaseRecord::defineRelations()
	 *
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'migrations' => array(static::HAS_MANY, 'MigrationRecord', 'pluginId'),
		);
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseRecord::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'class' => array(AttributeType::ClassName, 'required' => true),
			'version' => array('maxLength' => 15, 'column' => ColumnType::Varchar, 'required' => true),
			'schemaVersion' => array('maxLength' => 15, 'column' => ColumnType::Varchar),
			'licenseKey' => array(AttributeType::String, 'column' => ColumnType::Char, 'length' => 24),
			'licenseKeyStatus' => array(AttributeType::Enum, 'values' => array('valid', 'invalid', 'mismatched', 'unknown'), 'default' => 'unknown', 'required' => true),
			'enabled' => AttributeType::Bool,
			'settings' => AttributeType::Mixed,
			'installDate' => array(AttributeType::DateTime, 'required' => true),
		);
	}
}
