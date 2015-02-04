<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;
use craft\app\enums\AttributeType;

/**
 * Class CategoryGroup record.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class CategoryGroup extends BaseRecord
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
		return '{{%categorygroups}}';
	}

	/**
	 * @inheritDoc BaseRecord::defineRelations()
	 *
	 * @return array
	 */
	public function defineRelations()
	{
		return [
			'structure'   => [static::BELONGS_TO, 'Structure', 'required' => true, 'onDelete' => static::CASCADE],
			'fieldLayout' => [static::BELONGS_TO, 'FieldLayout', 'onDelete' => static::SET_NULL],
			'locales'     => [static::HAS_MANY, 'CategoryGroupLocale', 'groupId'],
			'categories'  => [static::HAS_MANY, 'Category', 'groupId'],
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

	/**
	 * @inheritDoc BaseRecord::scopes()
	 *
	 * @return array
	 */
	public function scopes()
	{
		return [
			'ordered' => ['order' => 'name'],
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
			'name'      => [AttributeType::Name, 'required' => true],
			'handle'    => [AttributeType::Handle, 'required' => true],
			'hasUrls'   => [AttributeType::Bool, 'default' => true],
			'template'  => AttributeType::Template,
		];
	}
}
