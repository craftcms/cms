<?php
namespace Craft;

/**
 * Stores the info for a Craft release.
 *
 * @package craft.app.models
 */
class AppNewReleaseModel extends BaseModel
{
	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
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
