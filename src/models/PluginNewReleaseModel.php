<?php
namespace Craft;

/**
 * Stores the info for a plugin release.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.models
 * @since     1.0
 */
class PluginNewReleaseModel extends BaseModel
{
	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseModel::defineAttributes()
	 *
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
