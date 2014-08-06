<?php
namespace Craft;

/**
 * Class FieldGroupRecord
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.records
 * @since     1.0
 */
class FieldGroupRecord extends BaseRecord
{
	////////////////////
	// PUBLIC METHODS
	////////////////////

	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'fieldgroups';
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'fields' => array(static::HAS_MANY, 'FieldRecord', 'groupId'),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('name'), 'unique' => true),
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

	////////////////////
	// PROTECTED METHODS
	////////////////////

	/**
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'name' => array(AttributeType::Name, 'required' => true),
		);
	}
}
