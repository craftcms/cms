<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use craft\app\enums\AttributeType;

/**
 * Stores the info for a Craft release.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AppNewRelease extends BaseModel
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
