<?php
namespace Blocks;

/**
 * Stores the info for a Blocks release.
 */
class BlocksNewReleaseModel extends BaseModel
{
	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		$attributes['version']       = AttributeType::String;
		$attributes['build']         = AttributeType::String;
		$attributes['date']          = AttributeType::DateTime;
		$attributes['notes']         = AttributeType::String;
		$attributes['type']          = AttributeType::String;
		$attributes['critical']      = AttributeType::Bool;
		$attributes['manual']        = AttributeType::Bool;
		$attributes['breakpoint']    = AttributeType::Bool;

		return $attributes;
	}
}
