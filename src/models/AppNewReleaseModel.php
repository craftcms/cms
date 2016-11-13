<?php
namespace Craft;

/**
 * Stores the info for a Craft release.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.models
 * @since     1.0
 */
class AppNewReleaseModel extends BaseModel
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
		$attributes['date']          = AttributeType::DateTime;
		$attributes['localizedDate'] = AttributeType::String;
		$attributes['notes']         = AttributeType::String;
		$attributes['type']          = AttributeType::String;
		$attributes['critical']      = AttributeType::Bool;
		$attributes['manual']        = AttributeType::Bool;
		$attributes['breakpoint']    = AttributeType::Bool;

		return $attributes;
	}
}
