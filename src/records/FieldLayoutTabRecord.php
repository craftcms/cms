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
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'name'      => array(AttributeType::Name, 'required' => true),
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
