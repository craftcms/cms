<?php
namespace Craft;

/**
 * Stores the available Craft update info.
 *
 * @package craft.app.models
 */
class AppUpdateModel extends BaseModel
{
	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		$attributes['localBuild']              = AttributeType::String;
		$attributes['localVersion']            = AttributeType::String;
		$attributes['latestVersion']           = AttributeType::String;
		$attributes['latestBuild']             = AttributeType::String;
		$attributes['latestDate']              = AttributeType::DateTime;
		$attributes['targetVersion']           = AttributeType::String;
		$attributes['targetBuild']             = AttributeType::String;
		$attributes['realLatestVersion']       = AttributeType::String;
		$attributes['realLatestBuild']         = AttributeType::String;
		$attributes['realLatestDate']          = AttributeType::DateTime;
		$attributes['criticalUpdateAvailable'] = AttributeType::Bool;
		$attributes['manualUpdateRequired']    = AttributeType::Bool;
		$attributes['breakpointRelease']       = AttributeType::Bool;
		$attributes['licenseUpdated']          = AttributeType::String;
		$attributes['versionUpdateStatus']     = AttributeType::String;
		$attributes['manualDownloadEndpoint']  = AttributeType::String;
		$attributes['releases']                = AttributeType::Mixed;

		return $attributes;
	}

	/**
	 * @param string $name
	 * @param mixed  $value
	 * @return bool|void
	 */
	public function setAttribute($name, $value)
	{
		if ($name == 'releases')
		{
			$value = AppNewReleaseModel::populateModels($value);
		}

		parent::setAttribute($name, $value);
	}
}
