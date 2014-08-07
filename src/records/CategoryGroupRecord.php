<?php
namespace Craft;

/**
 * Category group record.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.records
 * @since     2.0
 */
class CategoryGroupRecord extends BaseRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'categorygroups';
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'structure'   => array(static::BELONGS_TO, 'StructureRecord', 'required' => true, 'onDelete' => static::CASCADE),
			'fieldLayout' => array(static::BELONGS_TO, 'FieldLayoutRecord', 'onDelete' => static::SET_NULL),
			'locales'     => array(static::HAS_MANY, 'CategoryGroupLocaleRecord', 'groupId'),
			'categories'  => array(static::HAS_MANY, 'CategoryRecord', 'groupId'),
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

	// Protected Methods
	// =========================================================================

	/**
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'name'      => array(AttributeType::Name, 'required' => true),
			'handle'    => array(AttributeType::Handle, 'required' => true),
			'hasUrls'   => array(AttributeType::Bool, 'default' => true),
			'template'  => AttributeType::Template,
		);
	}
}
