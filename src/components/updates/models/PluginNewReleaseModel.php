<?php
namespace Blocks;

/**
 * Stores the info for a plugin release.
 */
class PluginNewReleaseModel extends BaseModel
{
	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		$attributes['version']  = AttributeType::String;
		$attributes['date']     = AttributeType::DateTime;
		$attributes['notes']    = AttributeType::String;
		$attributes['critical'] = AttributeType::Bool;

		return $attributes;
	}
}
