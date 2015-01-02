<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use craft\app\enums\AttributeType;
use craft\app\models\AppNewRelease as AppNewReleaseModel;

/**
 * Stores the available Craft update info.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AppUpdate extends BaseModel
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseModel::setAttribute()
	 *
	 * @param string $name
	 * @param mixed  $value
	 *
	 * @return bool|null
	 */
	public function setAttribute($name, $value)
	{
		if ($name == 'releases')
		{
			$value = AppNewReleaseModel::populateModels($value);
		}

		parent::setAttribute($name, $value);
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseModel::defineAttributes()
	 *
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
}
