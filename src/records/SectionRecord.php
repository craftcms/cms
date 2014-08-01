<?php
namespace Craft;

/**
 * Class SectionRecord
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.records
 * @since     1.0
 */
class SectionRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'sections';
	}

	/**
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'name'             => array(AttributeType::Name, 'required' => true),
			'handle'           => array(AttributeType::Handle, 'required' => true),
			'type'             => array(AttributeType::Enum, 'values' => array(SectionType::Single, SectionType::Channel, SectionType::Structure), 'default' => SectionType::Channel, 'required' => true),
			'hasUrls'          => array(AttributeType::Bool, 'default' => true),
			'template'         => AttributeType::Template,
			'enableVersioning' => AttributeType::Bool,
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'locales'       => array(static::HAS_MANY, 'SectionLocaleRecord', 'sectionId'),
			'structure'     => array(static::BELONGS_TO, 'StructureRecord', 'onDelete' => static::SET_NULL),
		);
	}

	/**
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
	 * @return array
	 */
	public function scopes()
	{
		return array(
			'ordered' => array('order' => 'name'),
		);
	}
}
