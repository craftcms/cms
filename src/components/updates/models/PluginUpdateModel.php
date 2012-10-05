<?php
namespace Blocks;

/**
 * Stores the available plugin update info.
 */
class PluginUpdateModel extends BaseModel
{
	/**
	 * @return mixed
	 */
	public function defineAttributes()
	{
		$attributes['class']                   = AttributeType::String;
		$attributes['localVersion']            = AttributeType::String;
		$attributes['latestVersion']           = AttributeType::String;
		$attributes['latestDate']              = AttributeType::DateTime;
		$attributes['status']                  = AttributeType::Bool;
		$attributes['displayName']             = AttributeType::String;
		$attributes['notes']                   = AttributeType::String;
		$attributes['criticalUpdateAvailable'] = AttributeType::Bool;
		$attributes['releases']                = AttributeType::Mixed;

		return $attributes;
	}
}
