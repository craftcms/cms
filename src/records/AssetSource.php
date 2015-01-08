<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use craft\app\enums\AttributeType;

/**
 * Class AssetSource record.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AssetSource extends BaseRecord
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
		return 'assetsources';
	}

	/**
	 * @inheritDoc BaseRecord::defineRelations()
	 *
	 * @return array
	 */
	public function defineRelations()
	{
		return [
			'fieldLayout' => [static::BELONGS_TO, 'FieldLayout', 'onDelete' => static::SET_NULL],
		];
	}

	/**
	 * @inheritDoc BaseRecord::defineIndexes()
	 *
	 * @return array
	 */
	public function defineIndexes()
	{
		return [
			['columns' => ['name'], 'unique' => true],
			['columns' => ['handle'], 'unique' => true],
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
			'name'                => [AttributeType::Name, 'required' => true],
			'handle'              => [AttributeType::Handle, 'required' => true],
			'type'                => [AttributeType::ClassName, 'required' => true],
			'settings'            => AttributeType::Mixed,
			'sortOrder'           => AttributeType::SortOrder,
			'fieldLayoutId'       => AttributeType::Number,
		];
	}
}
