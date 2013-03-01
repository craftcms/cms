<?php
namespace Craft;

/**
 *
 */
class WidgetRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'widgets';
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'type'      => array(AttributeType::ClassName, 'required' => true),
			'sortOrder' => AttributeType::SortOrder,
			'settings'  => AttributeType::Mixed,
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'user' => array(static::BELONGS_TO, 'UserRecord', 'userId', 'required' => true, 'onDelete' => static::CASCADE),
		);
	}
}
