<?php
namespace Craft;

/**
 *
 */
class StructureModel extends BaseModel
{
	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'id'        => AttributeType::Number,
			'maxLevels' => AttributeType::Number,
		);
	}
}
