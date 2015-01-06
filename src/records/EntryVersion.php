<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use craft\app\Craft;
use craft\app\enums\AttributeType;

craft()->requireEdition(Craft::Client);

/**
 * Class EntryVersion record.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class EntryVersion extends BaseRecord
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
		return 'entryversions';
	}

	/**
	 * @inheritDoc BaseRecord::defineRelations()
	 *
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'entry'   => array(static::BELONGS_TO, 'Entry', 'required' => true, 'onDelete' => static::CASCADE),
			'section' => array(static::BELONGS_TO, 'Section', 'required' => true, 'onDelete' => static::CASCADE),
			'creator' => array(static::BELONGS_TO, 'User', 'required' => false, 'onDelete' => static::SET_NULL),
			'locale' => array(static::BELONGS_TO, 'Locale', 'locale', 'required' => true, 'onDelete' => static::CASCADE, 'onUpdate' => static::CASCADE),
		);
	}

	/**
	 * @inheritDoc BaseRecord::defineIndexes()
	 *
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('entryId', 'locale')),
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
		return [
			'locale' => [AttributeType::Locale, 'required' => true],
			'num'    => [AttributeType::Number, 'column' => ColumnType::SmallInt, 'unsigned' => true, 'required' => true],
			'notes'  => [AttributeType::String, 'column' => ColumnType::TinyText],
			'data'   => [AttributeType::Mixed, 'required' => true, 'column' => ColumnType::MediumText],
		];
	}
}
