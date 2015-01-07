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
	 * @inheritDoc BaseRecord::getTableName()
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'sections';
	}

	/**
	 * @inheritDoc BaseRecord::defineRelations()
	 *
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'locales'       => array(static::HAS_MANY, 'SectionLocale', 'sectionId'),
			'structure'     => array(static::BELONGS_TO, 'Structure', 'onDelete' => static::SET_NULL),
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
			array('columns' => array('name'), 'unique' => true),
			array('columns' => array('handle'), 'unique' => true),
		);
	}

	/**
	 * @inheritDoc BaseRecord::scopes()
	 *
	 * @return array
	 */
	public function scopes()
	{
		return array(
			'ordered' => array('order' => 'name'),
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
			'name'             => [AttributeType::Name, 'required' => true],
			'handle'           => [AttributeType::Handle, 'required' => true],
			'type'             => [AttributeType::Enum, 'values' => [SectionType::Single, SectionType::Channel, SectionType::Structure], 'default' => SectionType::Channel, 'required' => true],
			'hasUrls'          => [AttributeType::Bool, 'default' => true],
			'template'         => AttributeType::Template,
			'enableVersioning' => AttributeType::Bool,
		];
	}
}
