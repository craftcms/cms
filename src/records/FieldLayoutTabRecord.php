<?php
namespace Craft;

/**
 * Field record class
 */
class FieldLayoutTabRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'fieldlayouttabs';
	}

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'name'      => AttributeType::Name,
			'sortOrder' => AttributeType::SortOrder,
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'layout' => array(static::BELONGS_TO, 'FieldLayoutRecord', 'required' => true, 'onDelete' => static::CASCADE),
			'fields' => array(static::HAS_MANY, 'FieldLayoutFieldRecord', 'tabId', 'order' => 'fields.sortOrder'),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('sortOrder')),
		);
	}
}
