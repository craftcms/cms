<?php
namespace Craft;

/**
 * Link model class
 *
 * Used for transporting link data throughout the system.
 */
class LinkModel extends BaseModel
{
	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'id'             => AttributeType::Number,
			'criteriaId'     => AttributeType::Number,
			'leftEntryId'    => AttributeType::Number,
			'rightEntryId'   => AttributeType::Number,
			'leftSortOrder'  => AttributeType::Number,
			'rightSortOrder' => AttributeType::Number,
		);
	}
}
