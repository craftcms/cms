<?php
namespace Blocks;

/**
 * Stores the available Blocks update info.
 */
class BlocksUpdateModel extends BaseModel
{
	/**
	 * @return array|void
	 */
	public function defineAttributes()
	{
		$attributes['localBuild']              = AttributeType::String;
		$attributes['localVersion']            = AttributeType::String;
		$attributes['latestVersion']           = AttributeType::String;
		$attributes['latestBuild']             = AttributeType::String;
		$attributes['latestDate']              = AttributeType::DateTime;
		$attributes['criticalUpdateAvailable'] = AttributeType::Bool;
		$attributes['manualUpdateRequired']    = AttributeType::Bool;
		$attributes['versionUpdateStatus']     = AttributeType::String;
		$attributes['releases']                = AttributeType::Mixed;
		$attributes['manualDownloadEndpoint']  = AttributeType::String;

		return $attributes;
	}
}
