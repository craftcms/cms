<?php
namespace Blocks;

/**
 * Link model class
 *
 * Used for transporting link data throughout the system.
 */
class LinkModel extends BaseModel
{
	/**
	 * @return mixed
	 */
	public function defineAttributes()
	{
		$attributes = parent::defineAttributes();

		$attributes['name'] = AttributeType::String;

		return $attributes;
	}
}
