<?php
namespace Craft;

/**
 * Stores all of the available update info.
 */
class UpdateModel extends BaseModel
{
	/**
	 * @return array|void
	 */
	public function defineAttributes()
	{
		$attributes['craft']   = AttributeType::Mixed;
		$attributes['plugins']  = AttributeType::Mixed;
		$attributes['packages'] = AttributeType::Mixed;
		$attributes['errors']   = AttributeType::Mixed;

		return $attributes;
	}
}
