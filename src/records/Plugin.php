<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use craft\app\enums\AttributeType;
use craft\app\enums\ColumnType;

/**
 * Class Plugin record.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Plugin extends BaseRecord
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
		return [
			'migrations' => [static::HAS_MANY, 'Migration', 'pluginId'],
		];
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
		return [
			'class'       => [AttributeType::ClassName, 'required' => true],
			'version'     => ['maxLength' => 15, 'column' => ColumnType::Char, 'required' => true],
			'enabled'     => AttributeType::Bool,
			'settings'    => AttributeType::Mixed,
			'installDate' => [AttributeType::DateTime, 'required' => true],
		];
	}
}
