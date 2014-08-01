<?php
namespace Craft;

/**
 * Class AssetSourceRecord
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.records
 * @since     1.0
 */
class AssetSourceRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'assetsources';
	}

	/**
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'name'                => array(AttributeType::Name, 'required' => true),
			'type'                => array(AttributeType::ClassName, 'required' => true),
			'settings'            => AttributeType::Mixed,
			'sortOrder'           => AttributeType::SortOrder,
			'fieldLayoutId'       => AttributeType::Number,
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'fieldLayout' => array(static::BELONGS_TO, 'FieldLayoutRecord', 'onDelete' => static::SET_NULL),
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
}
