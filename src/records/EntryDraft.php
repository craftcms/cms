<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use Craft;
use craft\app\enums\AttributeType;
use craft\app\enums\ColumnType;

Craft::$app->requireEdition(Craft::Client);

/**
 * Stores entry drafts.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class EntryDraft extends BaseRecord
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
		return '{{%entrydrafts}}';
	}

	/**
	 * @inheritDoc BaseRecord::defineRelations()
	 *
	 * @return array
	 */
	public function defineRelations()
	{
		return [
			'entry'   => [static::BELONGS_TO, 'Entry', 'required' => true, 'onDelete' => static::CASCADE],
			'section' => [static::BELONGS_TO, 'Section', 'required' => true, 'onDelete' => static::CASCADE],
			'creator' => [static::BELONGS_TO, 'User', 'required' => true, 'onDelete' => static::CASCADE],
			'locale'  => [static::BELONGS_TO, 'Locale', 'locale', 'required' => true, 'onDelete' => static::CASCADE, 'onUpdate' => static::CASCADE],
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
			['columns' => ['entryId', 'locale']],
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
			'locale' => [AttributeType::Locale, 'required' => true],
			'name'   => [AttributeType::String, 'required' => true],
			'notes'  => [AttributeType::String, 'column' => ColumnType::TinyText],
			'data'   => [AttributeType::Mixed, 'required' => true, 'column' => ColumnType::MediumText],
		];
	}
}
