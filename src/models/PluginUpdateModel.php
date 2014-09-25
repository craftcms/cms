<?php
namespace Craft;

/**
 * Stores the available plugin update info.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
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
			$value = PluginUpdateModel::populateModels($value);
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
		$attributes['status']                  = AttributeType::Bool;
		$attributes['displayName']             = AttributeType::String;
		$attributes['criticalUpdateAvailable'] = AttributeType::Bool;
		$attributes['releases']                = AttributeType::Mixed;;

		return $attributes;
	}
}
