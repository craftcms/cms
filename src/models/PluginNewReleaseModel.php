<?php
namespace Craft;

/**
 * Stores the info for a plugin release.
 *
 * @package craft.app.models
 */
class PluginNewReleaseModel extends BaseModel
{
	/**
	 * @return array
	 */
	protected function defineAttributes()
	{
		$attributes['version']  = AttributeType::String;
		$attributes['date']     = AttributeType::DateTime;
		$attributes['notes']    = AttributeType::String;
		$attributes['critical'] = AttributeType::Bool;

		return $attributes;
	}
}
