<?php
namespace Blocks;

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
		$attributes['blocks']   = AttributeType::Mixed;
		$attributes['plugins']  = AttributeType::Mixed;
		$attributes['packages'] = AttributeType::Mixed;

		return $attributes;
	}
}
