<?php
namespace Craft;

/**
 * Class TagRecord
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.records
 * @since     1.1
 */
class TagRecord extends BaseRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'tags';
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'element' => array(static::BELONGS_TO, 'ElementRecord', 'id', 'required' => true, 'onDelete' => static::CASCADE),
			'group'   => array(static::BELONGS_TO, 'TagGroupRecord', 'required' => true, 'onDelete' => static::CASCADE),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('name', 'groupId'), 'unique' => true),
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
			'name'   => array(AttributeType::String, 'required' => true),
		);
	}
}
