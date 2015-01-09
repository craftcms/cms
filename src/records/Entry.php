<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use Craft;
use craft\app\enums\AttributeType;

/**
 * Class Entry record.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Entry extends BaseRecord
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
		return 'entries';
	}

	/**
	 * @inheritDoc BaseRecord::defineRelations()
	 *
	 * @return array
	 */
	public function defineRelations()
	{
		$relations = [
			'element' => [static::BELONGS_TO, 'Element', 'id', 'required' => true, 'onDelete' => static::CASCADE],
			'section' => [static::BELONGS_TO, 'Section', 'required' => true, 'onDelete' => static::CASCADE],
			'type'    => [static::BELONGS_TO, 'EntryType', 'onDelete' => static::CASCADE],
			'author'  => [static::BELONGS_TO, 'User', 'onDelete' => static::CASCADE],
		];

		if (Craft::$app->getEdition() == Craft::Pro)
		{
			$relations['versions'] = [static::HAS_MANY, 'EntryVersion', 'elementId'];
		}

		return $relations;
	}

	/**
	 * @inheritDoc BaseRecord::defineIndexes()
	 *
	 * @return array
	 */
	public function defineIndexes()
	{
		return [
			['columns' => ['sectionId']],
			['columns' => ['typeId']],
			['columns' => ['postDate']],
			['columns' => ['expiryDate']],
		];
	}

	/**
	 * @inheritDoc BaseRecord::scopes()
	 *
	 * @return array
	 */
	public function scopes()
	{
		return [
			'ordered' => ['order' => 'postDate'],
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
			'postDate'   => AttributeType::DateTime,
			'expiryDate' => AttributeType::DateTime,
		];
	}
}
