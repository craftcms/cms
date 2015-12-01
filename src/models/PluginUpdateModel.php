<?php
namespace Craft;

/**
 * Stores the available plugin update info.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.models
 * @since     1.0
 */
class PluginUpdateModel extends BaseModel
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
			$value = PluginNewReleaseModel::populateModels($value);
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
		$attributes['class']                   = AttributeType::String;
		$attributes['localVersion']            = AttributeType::String;
		$attributes['latestVersion']           = AttributeType::String;
		$attributes['latestDate']              = AttributeType::DateTime;
		$attributes['displayName']             = AttributeType::String;
		$attributes['criticalUpdateAvailable'] = AttributeType::Bool;
		$attributes['manualUpdateRequired']    = AttributeType::Bool;
		$attributes['manualDownloadEndpoint']  = AttributeType::String;
		$attributes['releases']                = AttributeType::Mixed;
		$attributes['status']                  = array(AttributeType::Enum, 'values' => array(PluginUpdateStatus::UpToDate, PluginUpdateStatus::UpdateAvailable, PluginUpdateStatus::Unknown, PluginUpdateStatus::Deleted), 'default' => PluginUpdateStatus::Unknown);

		return $attributes;
	}
}
