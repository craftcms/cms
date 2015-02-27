<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use craft\app\enums\AttributeType;

/**
 * Class AssetTransform record.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AssetTransform extends BaseRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public static function tableName()
	{
		return '{{%assettransforms}}';
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
			'name'                => [AttributeType::String, 'required' => true],
			'handle'              => [AttributeType::Handle, 'required' => true],
			'mode'                => [AttributeType::Enum, 'required' => true, 'values' => ['stretch', 'fit', 'crop'], 'default' => 'crop'],
			'position'            => [AttributeType::Enum, 'values' => ['top-left', 'top-center', 'top-right', 'center-left', 'center-center', 'center-right', 'bottom-left', 'bottom-center', 'bottom-right'], 'required' => true, 'default' => 'center-center'],
			'height'              => AttributeType::Number,
			'width'               => AttributeType::Number,
			'format'              => AttributeType::String,
			'quality'             => [AttributeType::Number, 'required' => false],
			'dimensionChangeTime' => AttributeType::DateTime
		];
	}
}
