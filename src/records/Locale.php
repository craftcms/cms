<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use craft\app\db\ActiveRecord;
use craft\app\enums\AttributeType;

/**
 * Stores the locales.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Locale extends ActiveRecord
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
		return '{{%locales}}';
	}

	/**
	 * @return string
	 */
	public static function primaryKey()
	{
		return ['locale'];
	}

	/**
	 * @inheritDoc ActiveRecord::defineIndexes()
	 *
	 * @return array
	 */
	public function defineIndexes()
	{
		return [
			['columns' => ['sortOrder']],
		];
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc ActiveRecord::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return [
			'locale'    => [AttributeType::Locale, 'required' => true, 'primaryKey' => true],
			'sortOrder' => AttributeType::SortOrder,
		];
	}
}
