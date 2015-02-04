<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use craft\app\enums\AttributeType;
use craft\app\enums\SectionType;

/**
 * Class Section record.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Section extends BaseRecord
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
		return '{{%sections}}';
	}

	/**
	 * @inheritDoc BaseRecord::defineRelations()
	 *
	 * @return array
	 */
	public function defineRelations()
	{
		return [
			'locales'       => [static::HAS_MANY, 'SectionLocale', 'sectionId'],
			'structure'     => [static::BELONGS_TO, 'Structure', 'onDelete' => static::SET_NULL],
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
			'name'             => [AttributeType::Name, 'required' => true],
			'handle'           => [AttributeType::Handle, 'required' => true],
			'type'             => [AttributeType::Enum, 'values' => [SectionType::Single, SectionType::Channel, SectionType::Structure], 'default' => SectionType::Channel, 'required' => true],
			'hasUrls'          => [AttributeType::Bool, 'default' => true],
			'template'         => AttributeType::Template,
			'enableVersioning' => AttributeType::Bool,
		];
	}
}
