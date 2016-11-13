<?php
namespace Craft;

/**
 * Class InfoModel
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.models
 * @since     1.0
 */
class InfoModel extends BaseModel
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
		return array(
			'id'            => AttributeType::Number,
			'version'       => array(AttributeType::String, 'required' => true, 'default' => '0'),
			'schemaVersion' => array(AttributeType::String, 'required' => true, 'default' => '0'),
			'edition'       => array(AttributeType::Number, 'required' => true, 'default' => 0),
			'siteName'      => array(AttributeType::Name, 'required' => true),
			'siteUrl'       => array(AttributeType::Url, 'required' => true),
			'timezone'      => array(AttributeType::String, 'maxLength' => 30, 'default' => date_default_timezone_get()),
			'on'            => AttributeType::Bool,
			'maintenance'   => AttributeType::Bool,
			'uid'           => AttributeType::String,
		);
	}
}
