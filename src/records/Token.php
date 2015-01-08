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
 * Token record.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Token extends BaseRecord
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
		return 'tokens';
	}

	/**
	 * @inheritDoc BaseRecord::defineIndexes()
	 *
	 * @return array
	 */
	public function defineIndexes()
	{
		return [
			['columns' => ['token'], 'unique' => true],
			['columns' => ['expiryDate']],
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
			'token'      => [AttributeType::String, 'column' => ColumnType::Char, 'length' => 32, 'required' => true],
			'route'      => [AttributeType::Mixed],
			'usageLimit' => [AttributeType::Number, 'min' => 0, 'max' => 255],
			'usageCount' => [AttributeType::Number, 'min' => 0, 'max' => 255],
			'expiryDate' => [AttributeType::DateTime, 'required' => true],
		];
	}
}
